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
 * Tests for the calendar events of the bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use local_tipbooking\models\booking;
use local_tipbooking\models\space;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Tests for the calendar events of the bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(calendar_sync::class)]
final class calendar_sync_test extends \advanced_testcase {
    /** @var space The space under test. */
    private space $space;

    /** @var array Slots of a day four days ahead. */
    private array $slots;

    /**
     * Creates a space with a multilang name and works out the slots of a day ahead.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        $generator = $this->generator();
        $this->space = $generator->create_space([
            'name' => '<span lang="en" class="multilang">Lab</span><span lang="ru" class="multilang">Лаборатория</span>',
        ]);
        $this->slots = (new slot_calculator($this->space))->day_slots($generator->day_ahead($this->space, 4));
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
     * Books a range of slots for a person.
     *
     * @param  int     $userid The person.
     * @param  int     $first  Index of the first slot.
     * @param  int     $last   Index of the last slot.
     * @return booking         The booking.
     */
    private function book(int $userid, int $first = 1, int $last = 2): booking {
        return $this->generator()->create_booking([
            'spaceid' => $this->space->id,
            'userid' => $userid,
            'timestart' => $this->slots[$first]['start'],
            'timeend' => $this->slots[$last]['end'],
        ]);
    }

    /**
     * The events of the plugin of one booking.
     *
     * @param  int   $bookingid The booking.
     * @return array            The event records.
     */
    private function events_of(int $bookingid): array {
        global $DB;
        return array_values($DB->get_records('event', [
            'component' => calendar_sync::COMPONENT,
            'instance' => $bookingid,
        ]));
    }

    /**
     * Booking creates one user event of the holder, with the same start and end, named in plain text.
     *
     * @return void
     */
    public function test_book_creates_user_event(): void {
        $user = $this->getDataGenerator()->create_user();

        $booking = $this->book((int) $user->id);

        $events = $this->events_of($booking->id);
        $this->assertCount(1, $events);
        $event = $events[0];
        $this->assertSame('user', $event->eventtype);
        $this->assertSame((int) $user->id, (int) $event->userid);
        $this->assertSame(0, (int) $event->courseid);
        $this->assertSame($booking->timestart, (int) $event->timestart);
        $this->assertSame($booking->timeend - $booking->timestart, (int) $event->timeduration);
        // The calendar prints names without filters: the name is stored in the language of the holder.
        $this->assertSame('Lab', $event->name);
        $this->assertStringContainsString('/local/tipbooking/space.php?id=' . $this->space->id, $event->description);
    }

    /**
     * The holder sees the event in their calendar; somebody else does not.
     *
     * @return void
     */
    public function test_only_the_holder_sees_the_event(): void {
        $holder = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $this->book((int) $holder->id);

        $this->setUser($holder);
        $seen = $this->upcoming_components();
        $this->assertSame([calendar_sync::COMPONENT], $seen);

        $this->setUser($other);
        $this->assertSame([], $this->upcoming_components());
    }

    /**
     * Components of the events in the upcoming view of the current user, as the calendar shows them.
     *
     * @return array The components.
     */
    private function upcoming_components(): array {
        $calendar = \calendar_information::create(time(), SITEID, null);
        [$data] = calendar_get_view($calendar, 'upcoming');
        return array_values(array_map(fn($event) => $event->component, $data->events));
    }

    /**
     * The holder cannot edit or delete the event by hand: it follows the booking.
     *
     * @return void
     */
    public function test_holder_cannot_edit_the_event(): void {
        $user = $this->getDataGenerator()->create_user();
        $booking = $this->book((int) $user->id);
        $this->setUser($user);

        $event = \calendar_event::load($this->events_of($booking->id)[0]->id);

        $this->assertFalse(calendar_edit_event_allowed($event, true));
        $this->assertFalse(calendar_delete_event_allowed($event));
    }

    /**
     * Cancelling, by the holder or by a manager, deletes the event.
     *
     * @return void
     */
    public function test_cancel_deletes_event(): void {
        $user = $this->getDataGenerator()->create_user();
        $booking = $this->book((int) $user->id);

        booking_manager::cancel($booking->id, (int) $user->id);

        $this->assertSame([], $this->events_of($booking->id));
    }

    /**
     * Deleting a space deletes the events of its upcoming and past bookings.
     *
     * @return void
     */
    public function test_space_deletion_deletes_events(): void {
        global $DB;

        $upcoming = $this->book((int) $this->getDataGenerator()->create_user()->id);
        $past = $this->book((int) $this->getDataGenerator()->create_user()->id, 4, 4);
        // Move the second booking to last week, as if it had already taken place.
        $DB->set_field(booking::TABLE, 'timestart', time() - WEEKSECS, ['id' => $past->id]);
        $DB->set_field(booking::TABLE, 'timeend', time() - WEEKSECS + HOURSECS, ['id' => $past->id]);
        $this->assertCount(1, $this->events_of($past->id));

        space_manager::delete($this->space->id);

        $this->assertSame([], $this->events_of($upcoming->id));
        $this->assertSame([], $this->events_of($past->id));
    }
}
