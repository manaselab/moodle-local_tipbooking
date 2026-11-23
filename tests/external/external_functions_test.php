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

namespace local_tipbooking\external;

use context_system;
use core_external\external_api;
use invalid_parameter_exception;
use local_tipbooking\booking_manager;
use local_tipbooking\models\space;
use local_tipbooking\slot_calculator;
use moodle_exception;
use require_login_exception;
use required_capability_exception;

/**
 * Tests for the external functions of the plugin.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(get_day_slots::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(book_range::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(cancel_booking::class)]
final class external_functions_test extends \advanced_testcase {
    /** @var space The space under test. */
    private space $space;

    /** @var string A day five days ahead. */
    private string $date;

    /** @var array The slots of that day. */
    private array $slots;

    /**
     * Creates a space.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator()->get_plugin_generator('local_tipbooking');
        $this->space = $generator->create_space();
        $this->date = $generator->day_ahead($this->space, 5);
        $this->slots = (new slot_calculator($this->space))->day_slots($this->date);
    }

    /**
     * Calls get_day_slots and cleans its answer.
     *
     * @param  int    $spaceid Identifier of the space.
     * @param  string $date    The day.
     * @return array           The answer.
     */
    private function day_slots(int $spaceid, string $date): array {
        return external_api::clean_returnvalue(
            get_day_slots::execute_returns(),
            get_day_slots::execute($spaceid, $date)
        );
    }

    /**
     * Calls book_range and cleans its answer.
     *
     * @param  int   $start Start of the range.
     * @param  int   $end   End of the range.
     * @return array        The answer.
     */
    private function book(int $start, int $end): array {
        return external_api::clean_returnvalue(
            book_range::execute_returns(),
            book_range::execute($this->space->id, $start, $end)
        );
    }

    /**
     * The happy path: read the day, book a range, read it again, cancel it.
     *
     * @return void
     */
    public function test_read_book_and_cancel(): void {
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $day = $this->day_slots($this->space->id, $this->date);
        $this->assertCount(count($this->slots), $day['slots']);
        $this->assertSame(count($this->slots), $day['freecount']);
        $this->assertArrayNotHasKey('mybooking', $day);

        $result = $this->book($this->slots[1]['start'], $this->slots[2]['end']);
        $this->assertSame(book_range::STATUS_BOOKED, $result['status']);
        $this->assertGreaterThan(0, $result['bookingid']);

        $day = $this->day_slots($this->space->id, $this->date);
        $this->assertSame($result['bookingid'], $day['mybooking']['id']);
        $this->assertSame(booking_manager::SLOT_MINE, $day['slots'][1]['status']);
        $this->assertSame(booking_manager::SLOT_BLOCKED, $day['slots'][0]['status']);

        $cancelled = external_api::clean_returnvalue(
            cancel_booking::execute_returns(),
            cancel_booking::execute($result['bookingid'])
        );
        $this->assertSame('cancelled', $cancelled['status']);
        $this->assertSame(count($this->slots), $this->day_slots($this->space->id, $this->date)['freecount']);
    }

    /**
     * The two expected refusals come back as a status, not as an error.
     *
     * @return void
     */
    public function test_conflicts_are_statuses(): void {
        $other = $this->getDataGenerator()->create_user();
        booking_manager::book($this->space->id, $other->id, $this->slots[0]['start'], $this->slots[1]['end']);

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        $taken = $this->book($this->slots[1]['start'], $this->slots[2]['end']);
        $this->assertSame(book_range::STATUS_SLOT_TAKEN, $taken['status']);
        $this->assertSame(0, $taken['bookingid']);
        $this->assertSame(get_string('error:slottaken', 'local_tipbooking'), $taken['message']);

        $this->book($this->slots[3]['start'], $this->slots[3]['end']);
        $again = $this->book($this->slots[5]['start'], $this->slots[5]['end']);
        $this->assertSame(book_range::STATUS_ALREADY_BOOKED, $again['status']);
    }

    /**
     * A range that does not match the slots is an error.
     *
     * @return void
     */
    public function test_invalid_range_is_an_error(): void {
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(moodle_exception::class);
        $this->book($this->slots[0]['start'] + 60, $this->slots[0]['end']);
    }

    /**
     * Parameters of the wrong type are refused.
     *
     * @return void
     */
    public function test_invalid_parameters(): void {
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(invalid_parameter_exception::class);
        get_day_slots::execute($this->space->id, '2026/12/01');
    }

    /**
     * Guests cannot read slots nor book.
     *
     * @return void
     */
    public function test_guest(): void {
        $this->setGuestUser();

        try {
            get_day_slots::execute($this->space->id, $this->date);
            $this->fail('A guest cannot read the slots.');
        } catch (required_capability_exception | require_login_exception $e) {
            $this->assertNotEmpty($e->getMessage());
        }

        $this->expectException(required_capability_exception::class);
        book_range::execute($this->space->id, $this->slots[0]['start'], $this->slots[0]['end']);
    }

    /**
     * Without the capability to book, nothing is booked.
     *
     * @return void
     */
    public function test_without_capability(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'user']);
        assign_capability('local/tipbooking:book', CAP_PROHIBIT, $roleid, context_system::instance()->id, true);
        $this->setUser($user);

        $this->expectException(required_capability_exception::class);
        book_range::execute($this->space->id, $this->slots[0]['start'], $this->slots[0]['end']);
    }

    /**
     * Somebody else cannot cancel a booking.
     *
     * @return void
     */
    public function test_cannot_cancel_other_booking(): void {
        $holder = $this->getDataGenerator()->create_user();
        $booking = booking_manager::book($this->space->id, $holder->id, $this->slots[0]['start'], $this->slots[0]['end']);
        $this->setUser($this->getDataGenerator()->create_user());

        $this->expectException(moodle_exception::class);
        cancel_booking::execute($booking->id);
    }
}
