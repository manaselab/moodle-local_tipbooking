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

use context_system;
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use local_tipbooking\booking_manager;
use local_tipbooking\calendar_sync;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\slot_calculator;
use local_tipbooking\space_manager;

/**
 * Tests for the privacy provider.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(provider::class)]
final class provider_test extends provider_testcase {
    /** @var space The space. */
    private space $space;

    /** @var \stdClass Manager of the space, who cancelled the booking of $two. */
    private \stdClass $manager;

    /** @var \stdClass Who created the space (its usermodified). */
    private \stdClass $creator;

    /** @var \stdClass Person with an active booking. */
    private \stdClass $one;

    /** @var \stdClass Person whose booking the manager cancelled. */
    private \stdClass $two;

    /** @var \stdClass Person with no data in the plugin. */
    private \stdClass $none;

    /**
     * A space with a manager, an active booking and one cancelled by the manager.
     *
     * @return void
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();
        $this->resetAfterTest();

        $this->creator = get_admin();
        $this->setUser($this->creator);
        $this->manager = $this->getDataGenerator()->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $this->getDataGenerator()->role_assign($roleid, $this->manager->id, context_system::instance()->id);

        $generator = $this->getDataGenerator()->get_plugin_generator('local_tipbooking');
        $this->space = $generator->create_space(['admins' => [$this->manager->id]]);
        $this->one = $this->getDataGenerator()->create_user();
        $this->two = $this->getDataGenerator()->create_user();
        $this->none = $this->getDataGenerator()->create_user();

        $slots = (new slot_calculator($this->space))->day_slots($generator->day_ahead($this->space, 2));
        $generator->create_booking([
            'spaceid' => $this->space->id, 'userid' => $this->one->id,
            'timestart' => $slots[0]['start'], 'timeend' => $slots[1]['end'],
        ]);
        $cancelled = $generator->create_booking([
            'spaceid' => $this->space->id, 'userid' => $this->two->id,
            'timestart' => $slots[3]['start'], 'timeend' => $slots[3]['end'],
        ]);
        booking_manager::cancel($cancelled->id, (int) $this->manager->id);
    }

    /**
     * The metadata names the three tables and the messages.
     *
     * @return void
     */
    public function test_get_metadata(): void {
        $items = provider::get_metadata(new collection('local_tipbooking'))->get_collection();
        $this->assertSame(
            [booking::TABLE, space_manager::ADMIN_TABLE, space::TABLE, 'core_message', 'core_calendar'],
            array_map(fn($item) => $item->get_name(), $items)
        );
    }

    /**
     * Everybody with data has the system context; somebody without data has none.
     *
     * @return void
     */
    public function test_get_contexts_for_userid(): void {
        foreach ([$this->one, $this->two, $this->manager, $this->creator] as $user) {
            $this->assertSame(
                [(int) context_system::instance()->id],
                array_map('intval', provider::get_contexts_for_userid((int) $user->id)->get_contextids())
            );
        }
        $this->assertEmpty(provider::get_contexts_for_userid((int) $this->none->id)->get_contextids());
    }

    /**
     * The system context lists who booked, who cancelled, the managers and the creator.
     *
     * @return void
     */
    public function test_get_users_in_context(): void {
        $userlist = new userlist(context_system::instance(), 'local_tipbooking');
        provider::get_users_in_context($userlist);

        $expected = array_map('intval', [$this->one->id, $this->two->id, $this->manager->id, $this->creator->id]);
        sort($expected);
        $actual = array_map('intval', $userlist->get_userids());
        sort($actual);
        $this->assertSame($expected, $actual);

        $other = new userlist(\context_user::instance($this->one->id), 'local_tipbooking');
        provider::get_users_in_context($other);
        $this->assertEmpty($other->get_userids());
    }

    /**
     * The person who booked gets their bookings; the manager, the booking they cancelled and
     * the space they manage.
     *
     * @return void
     */
    public function test_export_user_data(): void {
        $context = context_system::instance();
        $root = get_string('pluginname', 'local_tipbooking');

        $this->export_all_data_for_user((int) $this->one->id, 'local_tipbooking');
        $data = writer::with_context($context)->get_data([$root, get_string('privacy:path:bookings', 'local_tipbooking')]);
        $this->assertCount(1, $data->bookings);
        $this->assertSame($this->space->name, $data->bookings[0]['space']);

        writer::reset();
        $this->export_all_data_for_user((int) $this->manager->id, 'local_tipbooking');
        $cancelled = writer::with_context($context)->get_data([$root, get_string('privacy:path:cancelled', 'local_tipbooking')]);
        $this->assertCount(1, $cancelled->cancelled);
        $spaces = writer::with_context($context)->get_data([$root, get_string('privacy:path:spaces', 'local_tipbooking')]);
        $this->assertSame([$this->space->name], $spaces->managerof);
        $bookings = writer::with_context($context)->get_data([$root, get_string('privacy:path:bookings', 'local_tipbooking')]);
        $this->assertEmpty($bookings);
    }

    /**
     * Deleting one person removes their booking and its slots, and nothing of the others.
     *
     * @return void
     */
    public function test_delete_data_for_user(): void {
        global $DB;

        $context = context_system::instance();
        provider::delete_data_for_user(new approved_contextlist($this->one, 'local_tipbooking', [$context->id]));

        $this->assertFalse($DB->record_exists(booking::TABLE, ['userid' => $this->one->id]));
        $this->assertFalse($DB->record_exists('event', ['component' => calendar_sync::COMPONENT, 'userid' => $this->one->id]));
        $this->assertSame(0, $DB->count_records(booking_manager::SLOT_TABLE, ['spaceid' => $this->space->id]));
        $this->assertTrue($DB->record_exists(booking::TABLE, ['userid' => $this->two->id]));
        $this->assertSame([(int) $this->manager->id], space_manager::get_admin_ids($this->space->id));
    }

    /**
     * Deleting several people forgets the manager who cancelled, removes them as managers and
     * as the last to change a space.
     *
     * @return void
     */
    public function test_delete_data_for_users(): void {
        global $DB;

        $context = context_system::instance();
        provider::delete_data_for_users(new approved_userlist(
            $context,
            'local_tipbooking',
            [(int) $this->manager->id, (int) $this->creator->id]
        ));

        $cancelled = $DB->get_record(booking::TABLE, ['userid' => $this->two->id]);
        $this->assertSame(0, (int) $cancelled->cancelledby);
        $this->assertSame([], space_manager::get_admin_ids($this->space->id));
        $this->assertSame(0, (int) $DB->get_field(space::TABLE, 'usermodified', ['id' => $this->space->id]));
        $this->assertTrue($DB->record_exists(booking::TABLE, ['userid' => $this->one->id]));
        $this->assertEmpty(provider::get_contexts_for_userid((int) $this->manager->id)->get_contextids());
    }

    /**
     * Deleting everybody in the system context leaves the spaces without any personal data.
     *
     * @return void
     */
    public function test_delete_data_for_all_users_in_context(): void {
        global $DB;

        provider::delete_data_for_all_users_in_context(\context_user::instance($this->one->id));
        $this->assertSame(2, $DB->count_records(booking::TABLE));

        $this->assertSame(1, $DB->count_records('event', ['component' => calendar_sync::COMPONENT]));

        provider::delete_data_for_all_users_in_context(context_system::instance());
        $this->assertSame(0, $DB->count_records(booking::TABLE));
        $this->assertSame(0, $DB->count_records('event', ['component' => calendar_sync::COMPONENT]));
        $this->assertSame(0, $DB->count_records(booking_manager::SLOT_TABLE));
        $this->assertSame(0, $DB->count_records(space_manager::ADMIN_TABLE));
        $this->assertTrue($DB->record_exists(space::TABLE, ['id' => $this->space->id, 'usermodified' => 0]));
    }
}
