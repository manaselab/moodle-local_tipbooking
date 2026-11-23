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
 * Booking domain model.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\models;

use stdClass;

/**
 * A booking: one range of consecutive slots of one day of a space.
 *
 * While the booking is active its key is its day, which together with the space and the
 * user is unique, so a person holds at most one active booking per space and day. When the
 * booking is cancelled the key becomes minus its id, which frees the day for a new booking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking {
    /** @var string Database table name (without prefix). */
    public const TABLE = 'local_tipbooking_booking';

    /** @var int Status of a booking that holds its slots. */
    public const STATUS_ACTIVE = 1;

    /** @var int Status of a cancelled booking. */
    public const STATUS_CANCELLED = 0;

    /** @var int Record id (0 when not persisted yet). */
    public int $id = 0;

    /** @var int Space booked. */
    public int $spaceid = 0;

    /** @var int User who holds the booking. */
    public int $userid = 0;

    /** @var int Day of the booking as YYYYMMDD, in the time zone of the space. */
    public int $day = 0;

    /** @var int Start of the first slot. */
    public int $timestart = 0;

    /** @var int End of the last slot. */
    public int $timeend = 0;

    /** @var int One of the STATUS_ constants. */
    public int $status = self::STATUS_ACTIVE;

    /** @var int The day while active, minus the id once cancelled. */
    public int $activekey = 0;

    /** @var int User who cancelled the booking, 0 while active. */
    public int $cancelledby = 0;

    /** @var int When the booking was cancelled, 0 while active. */
    public int $timecancelled = 0;

    /** @var int Creation timestamp. */
    public int $timecreated = 0;

    /** @var int Last modification timestamp. */
    public int $timemodified = 0;

    /**
     * Builds a model instance from a database record.
     *
     * @param  stdClass $record Database record of the bookings table.
     * @return self             The hydrated model.
     */
    public static function from_record(stdClass $record): self {
        $booking = new self();
        $booking->id = (int) $record->id;
        $booking->spaceid = (int) $record->spaceid;
        $booking->userid = (int) $record->userid;
        $booking->day = (int) $record->day;
        $booking->timestart = (int) $record->timestart;
        $booking->timeend = (int) $record->timeend;
        $booking->status = (int) $record->status;
        $booking->activekey = (int) $record->activekey;
        $booking->cancelledby = (int) $record->cancelledby;
        $booking->timecancelled = (int) $record->timecancelled;
        $booking->timecreated = (int) $record->timecreated;
        $booking->timemodified = (int) $record->timemodified;
        return $booking;
    }

    /**
     * Converts the model to a database record.
     *
     * @return stdClass The record, without id when the booking is not persisted yet.
     */
    public function to_record(): stdClass {
        $record = (object) [
            'spaceid' => $this->spaceid,
            'userid' => $this->userid,
            'day' => $this->day,
            'timestart' => $this->timestart,
            'timeend' => $this->timeend,
            'status' => $this->status,
            'activekey' => $this->activekey,
            'cancelledby' => $this->cancelledby,
            'timecancelled' => $this->timecancelled,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
        if ($this->id) {
            $record->id = $this->id;
        }
        return $record;
    }

    /**
     * Whether the booking still holds its slots.
     *
     * @return bool True while the booking is active.
     */
    public function is_active(): bool {
        return $this->status === self::STATUS_ACTIVE;
    }
}
