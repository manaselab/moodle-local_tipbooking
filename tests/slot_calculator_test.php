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

use DateTimeImmutable;
use DateTimeZone;
use local_tipbooking\models\space;

/**
 * Tests for the slot calculator.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(slot_calculator::class)]
final class slot_calculator_test extends \basic_testcase {
    /**
     * Builds a space without touching the database.
     *
     * @param  array $fields Overrides.
     * @return space         The space.
     */
    private function make_space(array $fields = []): space {
        $space = new space();
        $space->timezone = $fields['timezone'] ?? 'Asia/Bishkek';
        $zone = new DateTimeZone($space->timezone);
        $space->datefrom = (new DateTimeImmutable($fields['from'] ?? '2026-12-01', $zone))->getTimestamp();
        $space->dateto = (new DateTimeImmutable($fields['to'] ?? '2027-03-31', $zone))->getTimestamp();
        $space->hourfrom = $fields['hourfrom'] ?? 510;
        $space->hourto = $fields['hourto'] ?? 1050;
        $space->slotduration = $fields['slotduration'] ?? 60;
        $space->weekdays = $fields['weekdays'] ?? space::WEEKDAYS_DEFAULT;
        return $space;
    }

    /**
     * Wall-clock time of a timestamp in a time zone.
     *
     * @param  int    $timestamp The moment.
     * @param  string $zone      The time zone.
     * @return string            The time, as H:i.
     */
    private function clock(int $timestamp, string $zone = 'Asia/Bishkek'): string {
        return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone($zone))->format('H:i');
    }

    /**
     * One-hour slots fill the opening hours.
     *
     * @return void
     */
    public function test_one_hour_slots(): void {
        $slots = (new slot_calculator($this->make_space()))->day_slots('2026-12-01');

        $this->assertCount(9, $slots);
        $this->assertSame('08:30', $this->clock($slots[0]['start']));
        $this->assertSame('17:30', $this->clock($slots[8]['end']));
    }

    /**
     * The last slot is dropped when it does not fit whole.
     *
     * @return void
     */
    public function test_last_slot_that_does_not_fit_is_dropped(): void {
        $space = $this->make_space(['hourfrom' => 480, 'hourto' => 780, 'slotduration' => 120]);
        $slots = (new slot_calculator($space))->day_slots('2026-12-01');

        $this->assertCount(2, $slots);
        $this->assertSame('08:00', $this->clock($slots[0]['start']));
        $this->assertSame('12:00', $this->clock($slots[1]['end']));
    }

    /**
     * Five-hour slots.
     *
     * @return void
     */
    public function test_five_hour_slots(): void {
        $space = $this->make_space(['hourfrom' => 480, 'hourto' => 1080, 'slotduration' => 300]);
        $slots = (new slot_calculator($space))->day_slots('2026-12-01');

        $this->assertCount(2, $slots);
        $this->assertSame('13:00', $this->clock($slots[1]['start']));
        $this->assertSame('18:00', $this->clock($slots[1]['end']));
    }

    /**
     * Days outside the weekdays or the period have no slots.
     *
     * @return void
     */
    public function test_days_that_cannot_be_booked(): void {
        $calculator = new slot_calculator($this->make_space());

        $this->assertSame([], $calculator->day_slots('2026-12-05'), 'Saturday is not in Monday to Friday.');
        $this->assertSame([], $calculator->day_slots('2026-11-30'), 'Before the period.');
        $this->assertSame([], $calculator->day_slots('2027-04-01'), 'After the period.');
        $this->assertSame([], $calculator->day_slots('2026-02-30'), 'Not a date.');
        $this->assertNotEmpty($calculator->day_slots('2027-03-31'), 'The last day of the period is bookable.');
    }

    /**
     * The day of a moment is the one of the space, not of UTC.
     *
     * @return void
     */
    public function test_date_of_uses_the_time_zone_of_the_space(): void {
        $calculator = new slot_calculator($this->make_space());
        $moment = (new DateTimeImmutable('2026-12-01 20:00', new DateTimeZone('UTC')))->getTimestamp();

        $this->assertSame('2026-12-02', $calculator->date_of($moment));
        $this->assertSame(20261202, slot_calculator::day_key('2026-12-02'));
    }

    /**
     * Slots follow the wall clock across the start of summer time.
     *
     * @return void
     */
    public function test_summer_time_change(): void {
        $space = $this->make_space([
            'timezone' => 'Europe/Madrid',
            'from' => '2027-03-01',
            'to' => '2027-04-30',
            'hourfrom' => 60,
            'hourto' => 300,
            'weekdays' => space::WEEKDAYS_ALL,
        ]);
        $slots = (new slot_calculator($space))->day_slots('2027-03-28');

        $this->assertCount(3, $slots, 'The hour the clock skips gives no slot.');
        $previous = 0;
        foreach ($slots as $slot) {
            $this->assertSame(HOURSECS, $slot['end'] - $slot['start']);
            $this->assertGreaterThan($previous, $slot['start']);
            $previous = $slot['start'];
        }
    }

    /**
     * A range must start and end on slot limits of one day.
     *
     * @return void
     */
    public function test_range_slots(): void {
        $calculator = new slot_calculator($this->make_space());
        $day = $calculator->day_slots('2026-12-01');
        $next = $calculator->day_slots('2026-12-02');

        $range = $calculator->range_slots($day[1]['start'], $day[3]['end']);
        $this->assertCount(3, $range);
        $this->assertSame($day[1]['start'], $range[0]['start']);

        $this->assertCount(1, $calculator->range_slots($day[0]['start'], $day[0]['end']));
        $this->assertNull($calculator->range_slots($day[1]['start'] + 60, $day[3]['end']), 'Not on a slot start.');
        $this->assertNull($calculator->range_slots($day[3]['start'], $day[1]['end']), 'Backwards.');
        $this->assertNull($calculator->range_slots($day[1]['start'], $next[0]['end']), 'Across two days.');
        $this->assertNull($calculator->range_slots($day[1]['start'], $day[1]['start']), 'Empty.');
    }
}
