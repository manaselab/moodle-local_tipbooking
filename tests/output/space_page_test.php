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

namespace local_tipbooking\output;

use DateTimeImmutable;
use local_tipbooking\booking_manager;
use local_tipbooking\slot_calculator;

/**
 * Tests for the page of a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(space_page::class)]
final class space_page_test extends \advanced_testcase {
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
     * Without a day in the address, the page opens the first day that still has a slot.
     *
     * @return void
     */
    public function test_default_day_is_next_open_day(): void {
        // Mondays only.
        $space = $this->generator()->create_space(['weekdays' => 1]);
        $user = $this->getDataGenerator()->create_user();

        $date = (new space_page($space, '', (int) $user->id, false, true, null))->get_date();

        $day = new DateTimeImmutable($date, $space->get_timezone());
        $today = new DateTimeImmutable($this->generator()->day_ahead($space, 0), $space->get_timezone());
        $this->assertSame('1', $day->format('N'));
        $this->assertGreaterThanOrEqual(0, (int) $today->diff($day)->format('%r%a'));
        $this->assertLessThanOrEqual(7, (int) $today->diff($day)->format('%r%a'));
    }

    /**
     * A valid day in the address is kept; an impossible one falls back to the default day.
     *
     * @return void
     */
    public function test_requested_day(): void {
        $space = $this->generator()->create_space();
        $user = $this->getDataGenerator()->create_user();
        $requested = $this->generator()->day_ahead($space, 3);

        $this->assertSame($requested, (new space_page($space, $requested, (int) $user->id, false, true, null))->get_date());

        $default = (new space_page($space, '', (int) $user->id, false, true, null))->get_date();
        $this->assertSame($default, (new space_page($space, '2026-02-30', (int) $user->id, false, true, null))->get_date());
    }

    /**
     * The grid marks taken, own and blocked slots, and the booking of the reader.
     *
     * @return void
     */
    public function test_grid_context(): void {
        $space = $this->generator()->create_space();
        $date = $this->generator()->day_ahead($space, 2);
        $slots = (new slot_calculator($space))->day_slots($date);
        $me = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->generator()->create_booking([
            'spaceid' => $space->id, 'userid' => $other->id,
            'timestart' => $slots[0]['start'], 'timeend' => $slots[0]['end'],
        ]);
        $this->generator()->create_booking([
            'spaceid' => $space->id, 'userid' => $me->id,
            'timestart' => $slots[2]['start'], 'timeend' => $slots[3]['end'],
        ]);

        $status = booking_manager::get_day_status($space, $date, (int) $me->id);
        $grid = space_page::grid_context($space, $date, $status, 'Day', true);

        $this->assertTrue($grid['hasmybooking']);
        $this->assertFalse($grid['full']);
        $this->assertSame(
            space_page::range_label($slots[2]['start'], $slots[3]['end'], 'Asia/Bishkek'),
            $grid['mybooking']['label']
        );
        $this->assertTrue($grid['slots'][0]['busy']);
        $this->assertTrue($grid['slots'][1]['blocked']);
        $this->assertTrue($grid['slots'][2]['mine']);
        $this->assertTrue($grid['slots'][3]['mine']);
        $this->assertEmpty(array_filter($grid['slots'], fn($slot) => $slot['free']));
    }

    /**
     * Without the capability to book, free slots are shown but cannot be chosen; a day with
     * every slot taken is full.
     *
     * @return void
     */
    public function test_grid_without_booking_and_full_day(): void {
        $space = $this->generator()->create_space(['hourfrom' => 540, 'hourto' => 660]);
        $date = $this->generator()->day_ahead($space, 2);
        $user = $this->getDataGenerator()->create_user();

        $status = booking_manager::get_day_status($space, $date, (int) $user->id);
        $grid = space_page::grid_context($space, $date, $status, 'Day', false);
        $this->assertCount(2, $grid['slots']);
        $this->assertFalse($grid['slots'][0]['free']);
        $this->assertTrue($grid['slots'][0]['blocked']);

        $slots = (new slot_calculator($space))->day_slots($date);
        $this->generator()->create_booking([
            'spaceid' => $space->id, 'userid' => $this->getDataGenerator()->create_user()->id,
            'timestart' => $slots[0]['start'], 'timeend' => $slots[1]['end'],
        ]);
        $status = booking_manager::get_day_status($space, $date, (int) $user->id);
        $this->assertTrue(space_page::grid_context($space, $date, $status, 'Day', true)['full']);
    }

    /**
     * The week starts on Monday, holds the chosen day and tells closed and past days.
     *
     * @return void
     */
    public function test_week(): void {
        global $PAGE;

        // Weekdays only.
        $space = $this->generator()->create_space(['weekdays' => 31]);
        $user = $this->getDataGenerator()->create_user();
        $date = $this->generator()->day_ahead($space, 9);

        $context = (new space_page($space, $date, (int) $user->id, false, true, null))
            ->export_for_template($PAGE->get_renderer('local_tipbooking'));

        $this->assertFalse($context['ended']);
        $this->assertCount(7, $context['week']);
        $this->assertSame('1', (new DateTimeImmutable($context['week'][0]['date']))->format('N'));
        $selected = array_values(array_filter($context['week'], fn($day) => $day['selected']));
        $this->assertSame($date, $selected[0]['date']);
        $this->assertTrue($context['week'][5]['closed']);
        $this->assertTrue($context['week'][6]['closed']);
        $this->assertTrue($context['week'][5]['disabled']);
        $this->assertFalse($context['week'][0]['past']);
        $this->assertSame($date, json_decode($context['config'])->date);
    }

    /**
     * After the bookable period the page says bookings are closed.
     *
     * @return void
     */
    public function test_ended_period(): void {
        global $PAGE;

        $space = $this->generator()->create_space([
            'datefrom' => time() - 30 * DAYSECS,
            'dateto' => time() - 3 * DAYSECS,
        ]);
        $user = $this->getDataGenerator()->create_user();

        $context = (new space_page($space, '', (int) $user->id, false, true, null))
            ->export_for_template($PAGE->get_renderer('local_tipbooking'));

        $this->assertTrue($context['ended']);
    }
}
