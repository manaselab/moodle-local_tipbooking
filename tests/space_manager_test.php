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

namespace local_tipbooking;

use context_system;
use local_tipbooking\event\space_created;
use local_tipbooking\event\space_deleted;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use moodle_exception;

/**
 * Tests for the space manager.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(space_manager::class)]
final class space_manager_test extends \advanced_testcase {
    /**
     * Prepares each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
    }

    /**
     * The generator of the plugin.
     *
     * @return \local_tipbooking_generator The generator.
     */
    private function generator(): \local_tipbooking_generator {
        return $this->getDataGenerator()->get_plugin_generator('local_tipbooking');
    }

    /**
     * A user who can manage spaces.
     *
     * @return \stdClass The user.
     */
    private function create_manager(): \stdClass {
        global $DB;
        $user = $this->getDataGenerator()->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'manager']);
        $this->getDataGenerator()->role_assign($roleid, $user->id, context_system::instance()->id);
        return $user;
    }

    /**
     * A space is created with its managers and fires its event.
     *
     * @return void
     */
    public function test_create(): void {
        $manager = $this->create_manager();
        $sink = $this->redirectEvents();

        $space = $this->generator()->create_space(['admins' => [$manager->id]]);

        $this->assertGreaterThan(0, $space->id);
        $this->assertSame([(int) $manager->id], space_manager::get_admin_ids($space->id));
        $this->assertTrue(space_manager::can_manage_space($space->id, (int) $manager->id));
        $this->assertNotEmpty(array_filter($sink->get_events(), fn($e) => $e instanceof space_created));
    }

    /**
     * Only people who can manage spaces can be its managers, and there must be one at least.
     *
     * @return void
     */
    public function test_invalid_admins(): void {
        $user = $this->getDataGenerator()->create_user();

        try {
            $this->generator()->create_space(['admins' => [$user->id]]);
            $this->fail('A user without the capability cannot be a manager.');
        } catch (moodle_exception $e) {
            $this->assertSame('error:invalidadmin', $e->errorcode);
        }

        $this->expectException(moodle_exception::class);
        $this->generator()->create_space(['admins' => []]);
    }

    /**
     * Saving a space replaces its managers.
     *
     * @return void
     */
    public function test_update_replaces_admins(): void {
        $one = $this->create_manager();
        $two = $this->create_manager();
        $space = $this->generator()->create_space(['admins' => [$one->id]]);

        $space->name = 'Renamed';
        space_manager::update($space, [$two->id]);

        $this->assertSame('Renamed', space_manager::get($space->id)->name);
        $this->assertSame([(int) $two->id], space_manager::get_admin_ids($space->id));
    }

    /**
     * Search by text, hidden spaces left out, and paging after filtering.
     *
     * @return void
     */
    public function test_search_visible_by_text(): void {
        $this->generator()->create_space(['name' => 'Prusa 3D printer']);
        $this->generator()->create_space(['name' => 'Meeting room', 'description' => 'With a 3D screen']);
        $this->generator()->create_space(['name' => 'Laser cutter']);
        $this->generator()->create_space(['name' => 'Hidden 3D lab', 'visible' => 0]);

        $all = space_manager::search_visible('', null);
        $this->assertSame(3, $all['total']);

        $found = space_manager::search_visible('3d', null);
        $this->assertSame(2, $found['total']);
        $this->assertSame(['Meeting room', 'Prusa 3D printer'], array_map(fn($s) => $s->name, $found['spaces']));

        $page = space_manager::search_visible('', null, 2, 2);
        $this->assertSame(3, $page['total']);
        $this->assertCount(1, $page['spaces']);
    }

    /**
     * The "free on" filter leaves out full, closed and out-of-period spaces.
     *
     * @return void
     */
    public function test_search_visible_by_date(): void {
        $open = $this->generator()->create_space(['name' => 'Open']);
        $full = $this->generator()->create_space(['name' => 'Full', 'hourfrom' => 540, 'hourto' => 600]);
        $this->generator()->create_space(['name' => 'Closed', 'weekdays' => 0]);
        $date = $this->generator()->day_ahead($open, 5);
        $this->generator()->create_space([
            'name' => 'Ended',
            'dateto' => (new \DateTimeImmutable($this->generator()->day_ahead($open, 1), $open->get_timezone()))->getTimestamp(),
        ]);

        $slot = (new slot_calculator($full))->day_slots($date)[0];
        $user = $this->getDataGenerator()->create_user();
        $this->generator()->create_booking([
            'spaceid' => $full->id,
            'userid' => $user->id,
            'timestart' => $slot['start'],
            'timeend' => $slot['end'],
        ]);

        $found = space_manager::search_visible('', $date);
        $this->assertSame(['Open'], array_map(fn($s) => $s->name, $found['spaces']));
    }

    /**
     * Deleting a space cancels its upcoming bookings and removes everything of it.
     *
     * @return void
     */
    public function test_delete(): void {
        global $DB;

        $space = $this->generator()->create_space();
        $user = $this->getDataGenerator()->create_user();
        $slots = (new slot_calculator($space))->day_slots($this->generator()->day_ahead($space, 3));
        $this->generator()->create_booking([
            'spaceid' => $space->id,
            'userid' => $user->id,
            'timestart' => $slots[0]['start'],
            'timeend' => $slots[1]['end'],
        ]);
        $sink = $this->redirectEvents();

        space_manager::delete($space->id);

        $this->assertFalse($DB->record_exists(space::TABLE, ['id' => $space->id]));
        $this->assertSame(0, $DB->count_records(booking::TABLE, ['spaceid' => $space->id]));
        $this->assertSame(0, $DB->count_records(booking_manager::SLOT_TABLE, ['spaceid' => $space->id]));
        $this->assertSame(0, $DB->count_records(space_manager::ADMIN_TABLE, ['spaceid' => $space->id]));
        $this->assertNotEmpty(array_filter($sink->get_events(), fn($e) => $e instanceof space_deleted));
        $this->assertNotEmpty(array_filter(
            $sink->get_events(),
            fn($e) => $e instanceof event\booking_cancelled
        ));
    }
}
