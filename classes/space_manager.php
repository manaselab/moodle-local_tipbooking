<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Space manager.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use context_system;
use core\url;
use core_collator;
use core_user\fields;
use dml_missing_record_exception;
use local_tipbooking\event\space_created;
use local_tipbooking\event\space_deleted;
use local_tipbooking\event\space_updated;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use moodle_exception;

/**
 * Creates, changes and deletes spaces and keeps their managers.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_manager {
    /** @var string Table of the managers of each space. */
    public const ADMIN_TABLE = 'local_tipbooking_space_admin';

    /** @var string File area of the image of a space. */
    public const FILEAREA_IMAGE = 'image';

    /**
     * Loads a space.
     *
     * @param  int  $spaceid Identifier of the space.
     * @return space         The space.
     * @throws dml_missing_record_exception When it does not exist.
     */
    public static function get(int $spaceid): space {
        global $DB;
        return space::from_record($DB->get_record(space::TABLE, ['id' => $spaceid], '*', MUST_EXIST));
    }

    /**
     * Creates a space with its managers.
     *
     * @param  space $space    The space to create; its id is ignored.
     * @param  int[] $adminids Users notified of its bookings; each must be able to manage spaces.
     * @return space           The created space.
     * @throws moodle_exception When a manager cannot manage spaces.
     */
    public static function create(space $space, array $adminids): space {
        global $DB, $USER;

        self::validate_admins($adminids);

        $now = time();
        $space->id = 0;
        $space->usermodified = (int) $USER->id;
        $space->timecreated = $now;
        $space->timemodified = $now;

        $transaction = $DB->start_delegated_transaction();
        $space->id = (int) $DB->insert_record(space::TABLE, $space->to_record());
        self::save_admins($space->id, $adminids);
        $transaction->allow_commit();

        space_created::create([
            'objectid' => $space->id,
            'context' => context_system::instance(),
        ])->trigger();

        return $space;
    }

    /**
     * Saves the changes of a space and replaces its managers.
     *
     * Bookings already made are kept as they are, whatever changes in the opening hours.
     *
     * @param  space $space    The space, already persisted.
     * @param  int[] $adminids Users notified of its bookings; each must be able to manage spaces.
     * @return space           The saved space.
     * @throws moodle_exception When a manager cannot manage spaces.
     */
    public static function update(space $space, array $adminids): space {
        global $DB, $USER;

        self::validate_admins($adminids);

        $space->usermodified = (int) $USER->id;
        $space->timemodified = time();

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record(space::TABLE, $space->to_record());
        self::save_admins($space->id, $adminids);
        $transaction->allow_commit();

        self::trigger_updated($space->id);

        return $space;
    }

    /**
     * Shows or hides a space in the showcase.
     *
     * @param  int  $spaceid Identifier of the space.
     * @param  bool $visible Whether it is shown.
     * @return void
     */
    public static function set_visible(int $spaceid, bool $visible): void {
        global $DB, $USER;

        $DB->update_record(space::TABLE, (object) [
            'id' => $spaceid,
            'visible' => (int) $visible,
            'usermodified' => (int) $USER->id,
            'timemodified' => time(),
        ]);

        self::trigger_updated($spaceid);
    }

    /**
     * Deletes a space with its image, its managers and every booking.
     *
     * Bookings still to come are cancelled first, so their holders are notified.
     *
     * @param  int  $spaceid Identifier of the space.
     * @return void
     */
    public static function delete(int $spaceid): void {
        global $DB, $USER;

        $space = self::get($spaceid);

        foreach (booking_manager::get_upcoming_of_space($spaceid) as $upcoming) {
            booking_manager::cancel($upcoming->id, (int) $USER->id, true);
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records(booking_manager::SLOT_TABLE, ['spaceid' => $spaceid]);
        $DB->delete_records(booking::TABLE, ['spaceid' => $spaceid]);
        $DB->delete_records(self::ADMIN_TABLE, ['spaceid' => $spaceid]);
        $DB->delete_records(space::TABLE, ['id' => $spaceid]);
        $transaction->allow_commit();

        get_file_storage()->delete_area_files(
            context_system::instance()->id,
            'local_tipbooking',
            self::FILEAREA_IMAGE,
            $spaceid
        );

        $event = space_deleted::create([
            'objectid' => $space->id,
            'context' => context_system::instance(),
        ]);
        $event->add_record_snapshot(space::TABLE, $space->to_record());
        $event->trigger();
    }

    /**
     * The managers of a space.
     *
     * @param  int   $spaceid Identifier of the space.
     * @return int[]          Their user ids.
     */
    public static function get_admin_ids(int $spaceid): array {
        global $DB;
        return array_map('intval', $DB->get_fieldset_select(
            self::ADMIN_TABLE,
            'userid',
            'spaceid = :spaceid',
            ['spaceid' => $spaceid]
        ));
    }

    /**
     * Whether a user can manage a space and its bookings.
     *
     * Users who can manage every space can; so can the managers named in the space.
     *
     * @param  int  $spaceid Identifier of the space.
     * @param  int  $userid  Identifier of the user.
     * @return bool          True when the user can manage the space.
     */
    public static function can_manage_space(int $spaceid, int $userid): bool {
        global $DB;

        if (has_capability('local/tipbooking:manage', context_system::instance(), $userid)) {
            return true;
        }
        return $DB->record_exists(self::ADMIN_TABLE, ['spaceid' => $spaceid, 'userid' => $userid]);
    }

    /**
     * The people who can be managers of a space, to choose from.
     *
     * Only people who can manage spaces: they are few, so the list goes whole into the form.
     *
     * @return array User id => full name, sorted by name.
     */
    public static function get_manager_options(): array {
        $namefields = fields::for_name()->get_sql('u')->selects;
        $users = get_users_by_capability(context_system::instance(), 'local/tipbooking:manage', 'u.id' . $namefields);

        $options = [];
        foreach ($users as $user) {
            $options[(int) $user->id] = fullname($user);
        }
        core_collator::asort($options);

        return $options;
    }

    /**
     * The image of a space.
     *
     * @param  int      $spaceid Identifier of the space.
     * @return url|null          Address of the image, or null when the space has none.
     */
    public static function get_image_url(int $spaceid): ?url {
        $files = get_file_storage()->get_area_files(
            context_system::instance()->id,
            'local_tipbooking',
            self::FILEAREA_IMAGE,
            $spaceid,
            'filename',
            false
        );
        $file = reset($files);
        if (!$file) {
            return null;
        }

        return url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
    }

    /**
     * Checks that every manager of a space can manage spaces.
     *
     * @param  int[] $adminids User ids.
     * @return void
     * @throws moodle_exception When the list is empty or one of them cannot manage spaces.
     */
    private static function validate_admins(array $adminids): void {
        if ($adminids === []) {
            throw new moodle_exception('error:noadmins', 'local_tipbooking');
        }

        $context = context_system::instance();
        foreach ($adminids as $adminid) {
            if (!has_capability('local/tipbooking:manage', $context, (int) $adminid)) {
                throw new moodle_exception('error:invalidadmin', 'local_tipbooking');
            }
        }
    }

    /**
     * Replaces the managers of a space.
     *
     * @param  int   $spaceid  Identifier of the space.
     * @param  int[] $adminids User ids.
     * @return void
     */
    private static function save_admins(int $spaceid, array $adminids): void {
        global $DB;

        $DB->delete_records(self::ADMIN_TABLE, ['spaceid' => $spaceid]);
        foreach (array_unique(array_map('intval', $adminids)) as $adminid) {
            $DB->insert_record(self::ADMIN_TABLE, (object) ['spaceid' => $spaceid, 'userid' => $adminid]);
        }
    }

    /**
     * Fires the event of a changed space.
     *
     * @param  int  $spaceid Identifier of the space.
     * @return void
     */
    private static function trigger_updated(int $spaceid): void {
        space_updated::create([
            'objectid' => $spaceid,
            'context' => context_system::instance(),
        ])->trigger();
    }
}
