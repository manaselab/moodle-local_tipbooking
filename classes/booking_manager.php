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
 * Booking manager.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use context_system;
use dml_missing_record_exception;
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
 * Books and cancels ranges of slots, and answers what is free.
 *
 * The two rules of a booking are kept by the database, not only checked here: a slot can
 * be taken once (unique space and start in the slots table) and a person holds one active
 * booking per space and day (unique space, user and active key in the bookings table). The
 * checks before writing give the friendly answer; the unique keys settle the race between
 * two requests that pass the checks at the same time.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_manager {
    /** @var string Table of the slots taken by active bookings. */
    public const SLOT_TABLE = 'local_tipbooking_slot';

    /** @var string Status of a slot nobody has booked. */
    public const SLOT_FREE = 'free';

    /** @var string Status of a slot somebody else has booked. */
    public const SLOT_BUSY = 'busy';

    /** @var string Status of a slot of the reader's own booking. */
    public const SLOT_MINE = 'mine';

    /** @var string Status of a slot that has already started. */
    public const SLOT_PAST = 'past';

    /** @var string Status of a free slot the reader cannot take: they already booked that day. */
    public const SLOT_BLOCKED = 'blocked';

    /**
     * Books a range of consecutive slots of one day.
     *
     * @param  int     $spaceid   Identifier of the space.
     * @param  int     $userid    Identifier of the person who books.
     * @param  int     $timestart Start of the first slot.
     * @param  int     $timeend   End of the last slot.
     * @return booking            The booking.
     * @throws required_capability_exception When the person cannot book (guests never can).
     * @throws already_booked_today When the person already holds a booking of the space that day.
     * @throws slot_taken When part of the range is booked, also by a request a moment earlier.
     * @throws moodle_exception When the space is not available or the range is not valid.
     */
    public static function book(int $spaceid, int $userid, int $timestart, int $timeend): booking {
        global $DB;

        $space = self::get_bookable_space($spaceid);
        $context = context_system::instance();
        if (isguestuser($userid) || !has_capability('local/tipbooking:book', $context, $userid)) {
            throw new required_capability_exception($context, 'local/tipbooking:book', 'nopermissions', '');
        }

        $calculator = new slot_calculator($space);
        $slots = $calculator->range_slots($timestart, $timeend);
        if ($slots === null) {
            throw new moodle_exception('error:invalidrange', 'local_tipbooking');
        }
        if ($timestart <= time()) {
            throw new moodle_exception('error:rangeinpast', 'local_tipbooking');
        }

        $day = slot_calculator::day_key($calculator->date_of($timestart));
        if (self::get_user_day_booking($spaceid, $userid, $day) !== null) {
            throw new already_booked_today();
        }
        $starts = array_column($slots, 'start');
        if (self::any_slot_taken($spaceid, $starts)) {
            throw new slot_taken();
        }

        $now = time();
        $booking = new booking();
        $booking->spaceid = $spaceid;
        $booking->userid = $userid;
        $booking->day = $day;
        $booking->timestart = $timestart;
        $booking->timeend = $timeend;
        $booking->status = booking::STATUS_ACTIVE;
        $booking->activekey = $day;
        $booking->timecreated = $now;
        $booking->timemodified = $now;

        $transaction = $DB->start_delegated_transaction();
        try {
            $booking->id = (int) $DB->insert_record(booking::TABLE, $booking->to_record());
            foreach ($starts as $start) {
                $DB->insert_record(self::SLOT_TABLE, (object) [
                    'spaceid' => $spaceid,
                    'timestart' => $start,
                    'bookingid' => $booking->id,
                ]);
            }
            $transaction->allow_commit();
        } catch (dml_write_exception $e) {
            // Another request won the race. The unique key it hit does not show in the
            // exception, so the state left behind says which rule was broken.
            try {
                $transaction->rollback($e);
            } catch (dml_write_exception $ignored) {
                // The rollback rethrows the exception it is given: nothing else to do.
                unset($ignored);
            }
            if (self::get_user_day_booking($spaceid, $userid, $day) !== null) {
                throw new already_booked_today();
            }
            throw new slot_taken();
        }

        booking_created::create([
            'objectid' => $booking->id,
            'relateduserid' => $userid,
            'context' => $context,
            'other' => ['spaceid' => $spaceid],
        ])->trigger();

        notifier::booking_created($booking);

        return $booking;
    }

    /**
     * Cancels a booking and frees its slots.
     *
     * The holder can cancel while the booking has not started; a manager of the space can
     * cancel it until it ends.
     *
     * @param  int     $bookingid Identifier of the booking.
     * @param  int     $byuserid  Identifier of who cancels.
     * @param  bool    $force     Skip the permission and time checks, for a caller that already
     *                            decided, such as the deletion of the space.
     * @return booking            The cancelled booking.
     * @throws moodle_exception When the booking is not active or the user cannot cancel it.
     */
    public static function cancel(int $bookingid, int $byuserid, bool $force = false): booking {
        global $DB;

        $booking = self::get($bookingid);
        if (!$booking->is_active()) {
            throw new moodle_exception('error:notactive', 'local_tipbooking');
        }

        if (!$force) {
            $now = time();
            $isowner = $booking->userid === $byuserid;
            $canmanage = space_manager::can_manage_space($booking->spaceid, $byuserid);
            $ownercan = $isowner && $booking->timestart > $now;
            $managercan = $canmanage && $booking->timeend > $now;
            if (!$ownercan && !$managercan) {
                throw new moodle_exception('error:cannotcancel', 'local_tipbooking');
            }
        }

        $now = time();
        $booking->status = booking::STATUS_CANCELLED;
        $booking->activekey = -$booking->id;
        $booking->cancelledby = $byuserid;
        $booking->timecancelled = $now;
        $booking->timemodified = $now;

        $transaction = $DB->start_delegated_transaction();
        $DB->update_record(booking::TABLE, $booking->to_record());
        $DB->delete_records(self::SLOT_TABLE, ['bookingid' => $booking->id]);
        $transaction->allow_commit();

        $event = booking_cancelled::create([
            'objectid' => $booking->id,
            'relateduserid' => $booking->userid,
            'context' => context_system::instance(),
            'other' => ['spaceid' => $booking->spaceid],
        ]);
        $event->trigger();

        notifier::booking_cancelled($booking);

        return $booking;
    }

    /**
     * Loads a booking.
     *
     * @param  int     $bookingid Identifier of the booking.
     * @return booking            The booking.
     * @throws dml_missing_record_exception When it does not exist.
     */
    public static function get(int $bookingid): booking {
        global $DB;
        return booking::from_record($DB->get_record(booking::TABLE, ['id' => $bookingid], '*', MUST_EXIST));
    }

    /**
     * The slots of a day of a space as one person sees them.
     *
     * @param  space $space  The space.
     * @param  string $date  The day, as Y-m-d in the time zone of the space.
     * @param  int   $userid Identifier of the reader.
     * @param  int|null $now The current moment; null for now.
     * @return array         ['slots' => list of ['start', 'end', 'status'], 'mybooking' => booking|null].
     */
    public static function get_day_status(space $space, string $date, int $userid, ?int $now = null): array {
        global $DB;

        $now = $now ?? time();
        $calculator = new slot_calculator($space);
        $slots = $calculator->day_slots($date);
        $mybooking = self::get_user_day_booking($space->id, $userid, slot_calculator::day_key($date));

        $taken = [];
        if ($slots !== []) {
            $taken = $DB->get_records_sql_menu(
                "SELECT s.timestart, b.userid
                   FROM {" . self::SLOT_TABLE . "} s
                   JOIN {" . booking::TABLE . "} b ON b.id = s.bookingid
                  WHERE s.spaceid = :spaceid AND s.timestart >= :from AND s.timestart < :to",
                ['spaceid' => $space->id, 'from' => $slots[0]['start'], 'to' => end($slots)['end']]
            );
        }

        $result = [];
        foreach ($slots as $slot) {
            if (isset($taken[$slot['start']])) {
                $status = (int) $taken[$slot['start']] === $userid ? self::SLOT_MINE : self::SLOT_BUSY;
            } else if ($slot['start'] <= $now) {
                $status = self::SLOT_PAST;
            } else if ($mybooking !== null) {
                $status = self::SLOT_BLOCKED;
            } else {
                $status = self::SLOT_FREE;
            }
            $result[] = $slot + ['status' => $status];
        }

        return ['slots' => $result, 'mybooking' => $mybooking];
    }

    /**
     * The active booking of a person in a space on a day.
     *
     * @param  int          $spaceid Identifier of the space.
     * @param  int          $userid  Identifier of the person.
     * @param  int          $day     The day as YYYYMMDD.
     * @return booking|null          The booking, or null when there is none.
     */
    public static function get_user_day_booking(int $spaceid, int $userid, int $day): ?booking {
        global $DB;
        $record = $DB->get_record(booking::TABLE, ['spaceid' => $spaceid, 'userid' => $userid, 'activekey' => $day]);
        return $record ? booking::from_record($record) : null;
    }

    /**
     * The bookings of a person, upcoming or past.
     *
     * Upcoming are active bookings that have not ended, the soonest first; past are the ones
     * that ended or were cancelled, the latest first.
     *
     * @param  int       $userid   Identifier of the person.
     * @param  bool      $upcoming True for the upcoming ones, false for the past ones.
     * @param  int       $limit    How many at most; 0 for all.
     * @param  int       $offset   How many to skip.
     * @return booking[]           The bookings.
     */
    public static function get_user_bookings(int $userid, bool $upcoming, int $limit = 0, int $offset = 0): array {
        global $DB;

        [$where, $params, $order] = self::user_bookings_filter($userid, $upcoming);
        $records = $DB->get_records_select(booking::TABLE, $where, $params, $order, '*', $offset, $limit);

        return array_values(array_map([booking::class, 'from_record'], $records));
    }

    /**
     * How many bookings a person has, upcoming or past.
     *
     * @param  int  $userid   Identifier of the person.
     * @param  bool $upcoming True for the upcoming ones, false for the past ones.
     * @return int            The number of bookings.
     */
    public static function count_user_bookings(int $userid, bool $upcoming): int {
        global $DB;

        [$where, $params] = self::user_bookings_filter($userid, $upcoming);
        return $DB->count_records_select(booking::TABLE, $where, $params);
    }

    /**
     * The active bookings of a space that have not started.
     *
     * @param  int       $spaceid Identifier of the space.
     * @return booking[]          The bookings, the soonest first.
     */
    public static function get_upcoming_of_space(int $spaceid): array {
        global $DB;

        $records = $DB->get_records_select(
            booking::TABLE,
            'spaceid = :spaceid AND status = :active AND timestart > :now',
            ['spaceid' => $spaceid, 'active' => booking::STATUS_ACTIVE, 'now' => time()],
            'timestart ASC'
        );

        return array_values(array_map([booking::class, 'from_record'], $records));
    }

    /**
     * Loads a space that can be booked.
     *
     * @param  int   $spaceid Identifier of the space.
     * @return space          The space.
     * @throws moodle_exception When it does not exist or is hidden.
     */
    private static function get_bookable_space(int $spaceid): space {
        try {
            $space = space_manager::get($spaceid);
        } catch (dml_missing_record_exception $e) {
            throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
        }
        if (!$space->visible) {
            throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
        }
        return $space;
    }

    /**
     * Whether any of some slots of a space is taken.
     *
     * @param  int   $spaceid Identifier of the space.
     * @param  int[] $starts  Starts of the slots.
     * @return bool           True when at least one is taken.
     */
    private static function any_slot_taken(int $spaceid, array $starts): bool {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal($starts, SQL_PARAMS_NAMED, 'start');
        $params['spaceid'] = $spaceid;
        return $DB->record_exists_select(self::SLOT_TABLE, "spaceid = :spaceid AND timestart $insql", $params);
    }

    /**
     * The condition and order of the bookings of a person.
     *
     * @param  int   $userid   Identifier of the person.
     * @param  bool  $upcoming True for the upcoming ones, false for the past ones.
     * @return array           [where, params, order].
     */
    private static function user_bookings_filter(int $userid, bool $upcoming): array {
        $params = ['userid' => $userid, 'active' => booking::STATUS_ACTIVE, 'now' => time()];
        if ($upcoming) {
            return ['userid = :userid AND status = :active AND timeend > :now', $params, 'timestart ASC'];
        }
        return ['userid = :userid AND (status <> :active OR timeend <= :now)', $params, 'timestart DESC'];
    }
}
