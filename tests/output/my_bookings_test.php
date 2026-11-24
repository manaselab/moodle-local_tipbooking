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
use local_tipbooking\models\booking;
use local_tipbooking\models\space;

/**
 * Tests for the list of bookings of the reader.
 *
 * The list only formats what it receives, so the bookings are built in memory around a
 * fixed moment.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(my_bookings::class)]
final class my_bookings_test extends \advanced_testcase {
    /** @var space Space of the bookings. */
    private space $space;

    /** @var DateTimeImmutable The current moment: Thursday 3 December 2026, 12:00 in Bishkek. */
    private DateTimeImmutable $now;

    /**
     * Prepares each test.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        $this->space = $this->getDataGenerator()->get_plugin_generator('local_tipbooking')->create_space(['name' => 'Lab']);
        $this->now = new DateTimeImmutable('2026-12-03 12:00', $this->space->get_timezone());
    }

    /**
     * A booking in memory.
     *
     * @param  int    $id     Its identifier.
     * @param  string $start  Start, in the time zone of the space.
     * @param  int    $hours  Length.
     * @param  bool   $active Whether it is active.
     * @return booking        The booking.
     */
    private function booking(int $id, string $start, int $hours, bool $active = true): booking {
        $from = new DateTimeImmutable($start, $this->space->get_timezone());
        $booking = new booking();
        $booking->id = $id;
        $booking->spaceid = $this->space->id;
        $booking->day = (int) $from->format('Ymd');
        $booking->timestart = $from->getTimestamp();
        $booking->timeend = $from->modify("+{$hours} hours")->getTimestamp();
        $booking->status = $active ? booking::STATUS_ACTIVE : booking::STATUS_CANCELLED;
        return $booking;
    }

    /**
     * Exports a list.
     *
     * @param  booking[] $bookings The bookings.
     * @param  bool      $upcoming Whether they are upcoming.
     * @return array               The context.
     */
    private function export(array $bookings, bool $upcoming): array {
        global $PAGE;
        $list = new my_bookings(
            $bookings,
            [$this->space->id => $this->space],
            [],
            $upcoming,
            '',
            '',
            $this->now->getTimestamp()
        );
        return $list->export_for_template($PAGE->get_renderer('local_tipbooking'));
    }

    /**
     * Upcoming bookings are grouped by day, with their state, and only the ones that have
     * not started can be cancelled.
     *
     * @return void
     */
    public function test_upcoming_groups(): void {
        $context = $this->export([
            $this->booking(1, '2026-12-03 11:00', 2),
            $this->booking(2, '2026-12-03 15:00', 1),
            $this->booking(3, '2026-12-04 09:00', 3),
            $this->booking(4, '2026-12-10 10:00', 1),
        ], true);

        $this->assertTrue($context['hasbookings']);
        $this->assertSame(['Today', 'Tomorrow', userdate(
            $this->booking(4, '2026-12-10 10:00', 1)->timestart,
            get_string('strftimedaydate', 'langconfig'),
            'Asia/Bishkek'
        )], array_column($context['groups'], 'title'));

        [$inprogress, $confirmed] = $context['groups'][0]['items'];
        $this->assertTrue($inprogress['inprogress']);
        $this->assertFalse($inprogress['cancancel']);
        $this->assertTrue($confirmed['confirmed']);
        $this->assertTrue($confirmed['cancancel']);
        $this->assertSame('15:00–16:00', $confirmed['range']);

        $tomorrow = $context['groups'][1]['items'][0];
        $this->assertSame('3 h', $tomorrow['duration']);
        $this->assertStringContainsString('date=2026-12-04', $tomorrow['spaceurl']);
    }

    /**
     * Past bookings show as finished or cancelled, without actions.
     *
     * @return void
     */
    public function test_past(): void {
        $context = $this->export([
            $this->booking(5, '2026-12-08 10:00', 1, false),
            $this->booking(6, '2026-12-02 10:00', 2),
        ], false);

        $items = array_merge(...array_column($context['groups'], 'items'));
        $this->assertSame([
            get_string('mybookings:statuscancelled', 'local_tipbooking'),
            get_string('mybookings:statusfinished', 'local_tipbooking'),
        ], array_column($items, 'status'));
        $this->assertEmpty(array_filter(array_column($items, 'cancancel')));
        $this->assertSame('Yesterday', $context['groups'][1]['title']);
    }

    /**
     * Without bookings the list is empty, and a hidden space is not linked.
     *
     * @return void
     */
    public function test_empty_and_hidden_space(): void {
        $this->assertFalse($this->export([], true)['hasbookings']);

        $this->space->visible = 0;
        $context = $this->export([$this->booking(7, '2026-12-04 09:00', 1)], true);
        $this->assertSame('', $context['groups'][0]['items'][0]['spaceurl']);
    }

    /**
     * Lengths in hours and minutes.
     *
     * @return void
     */
    public function test_duration(): void {
        $this->assertSame('2 h', my_bookings::duration(2 * HOURSECS));
        $this->assertSame('1 h 30 min', my_bookings::duration(90 * MINSECS));
        $this->assertSame('45 min', my_bookings::duration(45 * MINSECS));
    }
}
