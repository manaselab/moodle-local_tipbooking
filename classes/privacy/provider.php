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

namespace local_tipbooking\privacy;

use context;
use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\provider as metadata_provider;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\core_userlist_provider;
use core_privacy\local\request\plugin\provider as request_provider;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_tipbooking\booking_manager;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\space_manager;

/**
 * Privacy provider: bookings of each person, who cancelled them, the managers of each space
 * and who last changed a space.
 *
 * Every space and booking lives in the system context. Deleting the data of a person
 * removes their bookings (and the slots they held) without notifying anybody, since this
 * is not a cancellation but the removal of their data.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    core_userlist_provider,
    metadata_provider,
    request_provider {
    /**
     * Personal data the plugin stores or sends.
     *
     * @param  collection $collection The collection to add to.
     * @return collection             The collection.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(booking::TABLE, [
            'spaceid' => 'privacy:metadata:booking:spaceid',
            'userid' => 'privacy:metadata:booking:userid',
            'timestart' => 'privacy:metadata:booking:timestart',
            'timeend' => 'privacy:metadata:booking:timeend',
            'status' => 'privacy:metadata:booking:status',
            'cancelledby' => 'privacy:metadata:booking:cancelledby',
            'timecancelled' => 'privacy:metadata:booking:timecancelled',
            'timecreated' => 'privacy:metadata:booking:timecreated',
        ], 'privacy:metadata:booking');

        $collection->add_database_table(space_manager::ADMIN_TABLE, [
            'spaceid' => 'privacy:metadata:space_admin:spaceid',
            'userid' => 'privacy:metadata:space_admin:userid',
        ], 'privacy:metadata:space_admin');

        $collection->add_database_table(space::TABLE, [
            'usermodified' => 'privacy:metadata:space:usermodified',
            'timemodified' => 'privacy:metadata:space:timemodified',
        ], 'privacy:metadata:space');

        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');

        return $collection;
    }

    /**
     * The system context, when the person has any data in the plugin.
     *
     * @param  int         $userid The person.
     * @return contextlist         The contexts.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        if (self::has_data($userid)) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Everybody with data in the plugin, for the system context.
     *
     * @param  userlist $userlist The list to fill.
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if (!$userlist->get_context() instanceof context_system) {
            return;
        }

        $userlist->add_from_sql('userid', 'SELECT userid FROM {' . booking::TABLE . '}', []);
        $userlist->add_from_sql('cancelledby', 'SELECT cancelledby FROM {' . booking::TABLE . '} WHERE cancelledby > 0', []);
        $userlist->add_from_sql('userid', 'SELECT userid FROM {' . space_manager::ADMIN_TABLE . '}', []);
        $userlist->add_from_sql('usermodified', 'SELECT usermodified FROM {' . space::TABLE . '} WHERE usermodified > 0', []);
    }

    /**
     * Exports the bookings of the person, the ones they cancelled for others, the spaces they
     * manage and the ones they changed last.
     *
     * @param  approved_contextlist $contextlist The approved contexts of the person.
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (!self::includes_system($contextlist->get_contexts())) {
            return;
        }
        $userid = (int) $contextlist->get_user()->id;
        $context = context_system::instance();
        $root = get_string('pluginname', 'local_tipbooking');
        $path = fn(string $key): array => [$root, get_string('privacy:path:' . $key, 'local_tipbooking')];
        $names = $DB->get_records_menu(space::TABLE, null, '', 'id, name');
        $spacename = fn(int $id): string => format_string($names[$id] ?? '', true, ['context' => $context]);

        $mine = $DB->get_records(booking::TABLE, ['userid' => $userid], 'timestart ASC');
        if ($mine) {
            writer::with_context($context)->export_data($path('bookings'), (object) [
                'bookings' => array_values(array_map(fn($row) => [
                    'space' => $spacename((int) $row->spaceid),
                    'timestart' => transform::datetime($row->timestart),
                    'timeend' => transform::datetime($row->timeend),
                    'status' => get_string((int) $row->status === booking::STATUS_ACTIVE
                        ? 'mybookings:statusconfirmed' : 'mybookings:statuscancelled', 'local_tipbooking'),
                    'cancelledbyyou' => transform::yesno((int) $row->cancelledby === $userid),
                    'timecancelled' => $row->timecancelled ? transform::datetime($row->timecancelled) : '',
                    'timecreated' => transform::datetime($row->timecreated),
                ], $mine)),
            ]);
        }

        $cancelled = $DB->get_records_select(
            booking::TABLE,
            'cancelledby = :me AND userid <> :me2',
            ['me' => $userid, 'me2' => $userid],
            'timecancelled ASC'
        );
        if ($cancelled) {
            writer::with_context($context)->export_data($path('cancelled'), (object) [
                'cancelled' => array_values(array_map(fn($row) => [
                    'space' => $spacename((int) $row->spaceid),
                    'timestart' => transform::datetime($row->timestart),
                    'timeend' => transform::datetime($row->timeend),
                    'timecancelled' => transform::datetime($row->timecancelled),
                ], $cancelled)),
            ]);
        }

        $managed = $DB->get_fieldset_select(space_manager::ADMIN_TABLE, 'spaceid', 'userid = :userid', ['userid' => $userid]);
        $modified = $DB->get_records(space::TABLE, ['usermodified' => $userid], 'id', 'id, timemodified');
        if ($managed || $modified) {
            writer::with_context($context)->export_data($path('spaces'), (object) [
                'managerof' => array_values(array_map(fn($id) => $spacename((int) $id), $managed)),
                'lastchanged' => array_values(array_map(fn($row) => [
                    'space' => $spacename((int) $row->id),
                    'timemodified' => transform::datetime($row->timemodified),
                ], $modified)),
            ]);
        }
    }

    /**
     * Deletes the data of everybody in the system context.
     *
     * Spaces stay; they lose their managers and who changed them last.
     *
     * @param  context $context The context.
     * @return void
     */
    public static function delete_data_for_all_users_in_context(context $context): void {
        global $DB;

        if (!$context instanceof context_system) {
            return;
        }
        $DB->delete_records(booking_manager::SLOT_TABLE);
        $DB->delete_records(booking::TABLE);
        $DB->delete_records(space_manager::ADMIN_TABLE);
        $DB->set_field(space::TABLE, 'usermodified', 0);
    }

    /**
     * Deletes the data of one person.
     *
     * @param  approved_contextlist $contextlist The approved contexts of the person.
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        if (!self::includes_system($contextlist->get_contexts())) {
            return;
        }
        self::delete_users([(int) $contextlist->get_user()->id]);
    }

    /**
     * Deletes the data of several people.
     *
     * @param  approved_userlist $userlist The approved people.
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if (!$userlist->get_context() instanceof context_system) {
            return;
        }
        self::delete_users(array_map('intval', $userlist->get_userids()));
    }

    /**
     * Removes the bookings of some people with the slots they held, forgets them as the
     * ones who cancelled other bookings, as managers and as the last to change a space.
     *
     * @param  int[] $userids The people.
     * @return void
     */
    private static function delete_users(array $userids): void {
        global $DB;

        if ($userids === []) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records_select(
            booking_manager::SLOT_TABLE,
            'bookingid IN (SELECT id FROM {' . booking::TABLE . '} WHERE userid ' . $insql . ')',
            $params
        );
        $DB->delete_records_select(booking::TABLE, 'userid ' . $insql, $params);
        $DB->set_field_select(booking::TABLE, 'cancelledby', 0, 'cancelledby ' . $insql, $params);
        $DB->delete_records_select(space_manager::ADMIN_TABLE, 'userid ' . $insql, $params);
        $DB->set_field_select(space::TABLE, 'usermodified', 0, 'usermodified ' . $insql, $params);
        $transaction->allow_commit();
    }

    /**
     * Whether a person has any data in the plugin.
     *
     * @param  int  $userid The person.
     * @return bool         True when they do.
     */
    private static function has_data(int $userid): bool {
        global $DB;

        return $DB->record_exists(booking::TABLE, ['userid' => $userid])
            || $DB->record_exists(booking::TABLE, ['cancelledby' => $userid])
            || $DB->record_exists(space_manager::ADMIN_TABLE, ['userid' => $userid])
            || $DB->record_exists(space::TABLE, ['usermodified' => $userid]);
    }

    /**
     * Whether a list of contexts includes the system context.
     *
     * @param  context[] $contexts The contexts.
     * @return bool                True when it does.
     */
    private static function includes_system(array $contexts): bool {
        foreach ($contexts as $context) {
            if ($context instanceof context_system) {
                return true;
            }
        }
        return false;
    }
}
