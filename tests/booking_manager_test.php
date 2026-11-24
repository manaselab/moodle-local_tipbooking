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

use dml_write_exception;
use local_tipbooking\event\booking_cancelled;
use local_tipbooking\event\booking_created;
use local_tipbooking\exception\already_booked_today;
use local_tipbooking\exception\slot_taken;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use moodle_exception;
use required_capability_exception;

/**
 * Tests for the booking manager.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(booking_manager::class)]
final class booking_manager_test extends \advanced_testcase {
    /** @var space The space under test. */
    private space $space;

    /** @var array Slots of a day five days ahead. */
    private array $slots;

    /**
     * Creates a space and works out the slots of a day ahead.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->generator();
        $this->space = $generator->create_space();
        $this->slots = (new slot_calculator($this->space))->day_slots($generator->day_ahead($this->space, 5));
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
     * A range of several slots is booked and takes one row per slot.
     *
     * @return void
     */
    public function test_book_range(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $sink = $this->redirectEvents();

        $booking = booking_manager::book($this->space->id, $user->id, $this->slots[1]['start'], $this->slots[3]['end']);

        $this->assertTrue($booking->is_active());
        $this->assertSame($booking->day, $booking->activekey);
        $this->assertSame(3, $DB->count_records(booking_manager::SLOT_TABLE, ['bookingid' => $booking->id]));
        $events = array_values(array_filter($sink->get_events(), fn($e) => $e instanceof booking_created));
        $this->assertCount(1, $events);
        $this->assertSame((int) $user->id, (int) $events[0]->relateduserid);
    }

    /**
     * A range that does not match the slots is refused.
     *
     * @return void
     */
    public function test_invalid_range_is_refused(): void {
        $user = $this->getDataGenerator()->create_user();

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:invalidrange', 'local_tipbooking'));
        booking_manager::book($this->space->id, $user->id, $this->slots[1]['start'] + 60, $this->slots[2]['end']);
    }

    /**
     * A range that has started cannot be booked.
     *
     * @return void
     */
    public function test_past_range_is_refused(): void {
        $user = $this->getDataGenerator()->create_user();
        $past = (new slot_calculator($this->space))->day_slots($this->generator()->day_ahead($this->space, -2));

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:rangeinpast', 'local_tipbooking'));
        booking_manager::book($this->space->id, $user->id, $past[0]['start'], $past[0]['end']);
    }

    /**
     * A person holds one booking per space and day, and gets the day back after cancelling.
     *
     * @return void
     */
    public function test_one_booking_per_person_space_and_day(): void {
        $user = $this->getDataGenerator()->create_user();
        $first = booking_manager::book($this->space->id, $user->id, $this->slots[0]['start'], $this->slots[0]['end']);

        try {
            booking_manager::book($this->space->id, $user->id, $this->slots[5]['start'], $this->slots[6]['end']);
            $this->fail('A second booking of the same day must be refused.');
        } catch (already_booked_today $e) {
            $this->assertSame('error:alreadybookedtoday', $e->errorcode);
        }

        booking_manager::cancel($first->id, $user->id);
        $second = booking_manager::book($this->space->id, $user->id, $this->slots[5]['start'], $this->slots[6]['end']);
        $this->assertTrue($second->is_active());
    }

    /**
     * A range that overlaps the booking of somebody else is refused.
     *
     * @return void
     */
    public function test_overlap_is_refused(): void {
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();
        booking_manager::book($this->space->id, $one->id, $this->slots[2]['start'], $this->slots[4]['end']);

        $this->expectException(slot_taken::class);
        booking_manager::book($this->space->id, $two->id, $this->slots[4]['start'], $this->slots[5]['end']);
    }

    /**
     * The database refuses a slot taken twice, which settles simultaneous requests.
     *
     * @return void
     */
    public function test_slot_unique_key(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $booking = booking_manager::book($this->space->id, $user->id, $this->slots[0]['start'], $this->slots[0]['end']);

        $this->expectException(dml_write_exception::class);
        $DB->insert_record(booking_manager::SLOT_TABLE, (object) [
            'spaceid' => $this->space->id,
            'timestart' => $this->slots[0]['start'],
            'bookingid' => $booking->id + 1,
        ]);
    }

    /**
     * Guests cannot book.
     *
     * @return void
     */
    public function test_guest_cannot_book(): void {
        $this->expectException(required_capability_exception::class);
        booking_manager::book($this->space->id, (int) guest_user()->id, $this->slots[0]['start'], $this->slots[0]['end']);
    }

    /**
     * Hidden spaces cannot be booked.
     *
     * @return void
     */
    public function test_hidden_space_cannot_be_booked(): void {
        $user = $this->getDataGenerator()->create_user();
        space_manager::set_visible($this->space->id, false);

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessage(get_string('error:spacenotavailable', 'local_tipbooking'));
        booking_manager::book($this->space->id, $user->id, $this->slots[0]['start'], $this->slots[0]['end']);
    }

    /**
     * Cancelling frees the slots; only the holder or a manager of the space can cancel.
     *
     * @return void
     */
    public function test_cancel(): void {
        global $DB;

        $holder = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $booking = booking_manager::book($this->space->id, $holder->id, $this->slots[0]['start'], $this->slots[1]['end']);

        try {
            booking_manager::cancel($booking->id, $other->id);
            $this->fail('Somebody else cannot cancel the booking.');
        } catch (moodle_exception $e) {
            $this->assertSame('error:cannotcancel', $e->errorcode);
        }

        $sink = $this->redirectEvents();
        $cancelled = booking_manager::cancel($booking->id, (int) get_admin()->id);

        $this->assertSame(booking::STATUS_CANCELLED, $cancelled->status);
        $this->assertSame(-$booking->id, $cancelled->activekey);
        $this->assertSame((int) get_admin()->id, $cancelled->cancelledby);
        $this->assertSame(0, $DB->count_records(booking_manager::SLOT_TABLE, ['bookingid' => $booking->id]));
        $this->assertNotEmpty(array_filter($sink->get_events(), fn($e) => $e instanceof booking_cancelled));

        $this->expectException(moodle_exception::class);
        booking_manager::cancel($booking->id, $holder->id);
    }

    /**
     * Each person sees their own slots, the ones of others and, once they booked, the rest blocked.
     *
     * @return void
     */
    public function test_day_status(): void {
        $one = $this->getDataGenerator()->create_user();
        $two = $this->getDataGenerator()->create_user();
        $date = $this->generator()->day_ahead($this->space, 5);
        booking_manager::book($this->space->id, $one->id, $this->slots[0]['start'], $this->slots[1]['end']);

        $mine = booking_manager::get_day_status($this->space, $date, $one->id);
        $theirs = booking_manager::get_day_status($this->space, $date, $two->id);

        $this->assertSame(booking_manager::SLOT_MINE, $mine['slots'][1]['status']);
        $this->assertSame(booking_manager::SLOT_BLOCKED, $mine['slots'][2]['status']);
        $this->assertNotNull($mine['mybooking']);
        $this->assertSame(booking_manager::SLOT_BUSY, $theirs['slots'][0]['status']);
        $this->assertSame(booking_manager::SLOT_FREE, $theirs['slots'][2]['status']);
        $this->assertNull($theirs['mybooking']);
    }

    /**
     * Upcoming and past bookings of a person.
     *
     * @return void
     */
    public function test_user_bookings(): void {
        $user = $this->getDataGenerator()->create_user();
        $later = (new slot_calculator($this->space))->day_slots($this->generator()->day_ahead($this->space, 6));
        $soon = booking_manager::book($this->space->id, $user->id, $this->slots[0]['start'], $this->slots[0]['end']);
        $next = booking_manager::book($this->space->id, $user->id, $later[0]['start'], $later[0]['end']);
        booking_manager::cancel($next->id, $user->id);

        $upcoming = booking_manager::get_user_bookings($user->id, true);
        $this->assertCount(1, $upcoming);
        $this->assertSame($soon->id, $upcoming[0]->id);
        $this->assertSame(1, booking_manager::count_user_bookings($user->id, false));
    }

    /**
     * Upcoming bookings come soonest first; past and cancelled ones, latest first, by pages.
     *
     * @return void
     */
    public function test_user_bookings_order(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $day4 = (new slot_calculator($this->space))->day_slots($this->generator()->day_ahead($this->space, 4));
        $day2 = (new slot_calculator($this->space))->day_slots($this->generator()->day_ahead($this->space, 2));
        $later = booking_manager::book($this->space->id, $user->id, $day4[0]['start'], $day4[0]['end']);
        $sooner = booking_manager::book($this->space->id, $user->id, $day2[0]['start'], $day2[0]['end']);

        $this->assertSame(
            [$sooner->id, $later->id],
            array_map(fn($b) => $b->id, booking_manager::get_user_bookings($user->id, true))
        );

        // Ended bookings cannot be made through book(): they are written as they would be left.
        $ended = [];
        foreach ([10, 5] as $daysago) {
            $start = time() - $daysago * DAYSECS;
            $ended[$daysago] = $DB->insert_record(booking::TABLE, (object) [
                'spaceid' => $this->space->id, 'userid' => $user->id,
                'day' => (int) date('Ymd', $start), 'timestart' => $start, 'timeend' => $start + HOURSECS,
                'status' => booking::STATUS_ACTIVE, 'activekey' => (int) date('Ymd', $start),
                'cancelledby' => 0, 'timecancelled' => 0, 'timecreated' => $start, 'timemodified' => $start,
            ]);
        }
        booking_manager::cancel($later->id, $user->id);

        $past = booking_manager::get_user_bookings($user->id, false);
        $this->assertSame([$later->id, $ended[5], $ended[10]], array_map(fn($b) => $b->id, $past));
        $this->assertSame(3, booking_manager::count_user_bookings($user->id, false));
        $this->assertSame(
            [$ended[5]],
            array_map(fn($b) => $b->id, booking_manager::get_user_bookings($user->id, false, 1, 1))
        );
    }
}
