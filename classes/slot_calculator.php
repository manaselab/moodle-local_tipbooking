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
 * Time slot calculator.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use DateTimeImmutable;
use local_tipbooking\models\space;

/**
 * Works out the time slots of a space, with no access to the database.
 *
 * Every slot is computed as a wall-clock time of the space ("10:30 in Bishkek") and only then
 * turned into a timestamp, so a change of summer time inside the opening hours moves the
 * slots with the clock instead of shifting every one after it by an hour.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slot_calculator {
    /**
     * Constructor.
     *
     * @param space $space The space whose slots are computed.
     */
    public function __construct(
        /** @var space The space whose slots are computed. */
        private readonly space $space,
    ) {
    }

    /**
     * The day of a moment, as the space sees it.
     *
     * @param  int    $timestamp Any moment.
     * @return string            The day, as Y-m-d in the time zone of the space.
     */
    public function date_of(int $timestamp): string {
        return $this->midnight_of_timestamp($timestamp)->format('Y-m-d');
    }

    /**
     * The key of a day, as stored in the bookings.
     *
     * @param  string $date The day, as Y-m-d.
     * @return int          The day as YYYYMMDD.
     */
    public static function day_key(string $date): int {
        return (int) str_replace('-', '', $date);
    }

    /**
     * Whether a day can be booked at all: inside the period and on an allowed weekday.
     *
     * @param  string $date The day, as Y-m-d in the time zone of the space.
     * @return bool         True when the day is bookable.
     */
    public function is_bookable_day(string $date): bool {
        $midnight = $this->midnight_of_date($date);
        if ($midnight === null) {
            return false;
        }

        $key = self::day_key($midnight->format('Y-m-d'));
        if ($key < self::day_key($this->date_of($this->space->datefrom))
                || $key > self::day_key($this->date_of($this->space->dateto))) {
            return false;
        }

        return $this->space->is_weekday_bookable((int) $midnight->format('N'));
    }

    /**
     * The slots of one day, in order.
     *
     * The last slot is dropped when it does not fit whole before closing time. A slot that a
     * change of summer time turns into nothing, or into the same moment as the previous one,
     * is dropped as well.
     *
     * @param  string $date The day, as Y-m-d in the time zone of the space.
     * @return array        List of ['start' => int, 'end' => int]; empty when the day is not bookable.
     */
    public function day_slots(string $date): array {
        if (!$this->is_bookable_day($date)) {
            return [];
        }

        $midnight = $this->midnight_of_date($date);
        $duration = $this->space->slotduration;
        if ($duration < 1) {
            return [];
        }

        $slots = [];
        $previous = null;
        for ($from = $this->space->hourfrom; $from + $duration <= $this->space->hourto; $from += $duration) {
            $start = $this->at_minutes($midnight, $from);
            $end = $this->at_minutes($midnight, $from + $duration);
            if ($end <= $start || ($previous !== null && $start <= $previous)) {
                continue;
            }
            $slots[] = ['start' => $start, 'end' => $end];
            $previous = $start;
        }

        return $slots;
    }

    /**
     * The slots a range covers, when the range is a valid booking.
     *
     * A valid range starts at the start of a slot and ends at the end of a slot of the same
     * day; the slots between them are consecutive by construction.
     *
     * @param  int        $timestart Start of the range.
     * @param  int        $timeend   End of the range.
     * @return array|null            The covered slots, as in day_slots(), or null when the range is not valid.
     */
    public function range_slots(int $timestart, int $timeend): ?array {
        if ($timeend <= $timestart) {
            return null;
        }

        $slots = $this->day_slots($this->date_of($timestart));
        $first = null;
        $last = null;
        foreach ($slots as $index => $slot) {
            if ($slot['start'] === $timestart) {
                $first = $index;
            }
            if ($slot['end'] === $timeend) {
                $last = $index;
            }
        }

        if ($first === null || $last === null || $last < $first) {
            return null;
        }

        return array_slice($slots, $first, $last - $first + 1);
    }

    /**
     * Midnight of a day in the time zone of the space.
     *
     * @param  string                 $date The day, as Y-m-d.
     * @return DateTimeImmutable|null       Midnight, or null when the date is not valid.
     */
    private function midnight_of_date(string $date): ?DateTimeImmutable {
        $midnight = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->space->get_timezone());
        if ($midnight === false || $midnight->format('Y-m-d') !== $date) {
            return null;
        }
        return $midnight;
    }

    /**
     * Midnight of the day of a moment, in the time zone of the space.
     *
     * @param  int               $timestamp Any moment.
     * @return DateTimeImmutable            Midnight of its day.
     */
    private function midnight_of_timestamp(int $timestamp): DateTimeImmutable {
        return (new DateTimeImmutable('@' . $timestamp))
            ->setTimezone($this->space->get_timezone())
            ->setTime(0, 0);
    }

    /**
     * The moment of a wall-clock time of a day.
     *
     * @param  DateTimeImmutable $midnight Midnight of the day, in the time zone of the space.
     * @param  int               $minutes  Minutes from midnight; 1440 is midnight of the next day.
     * @return int                         The timestamp.
     */
    private function at_minutes(DateTimeImmutable $midnight, int $minutes): int {
        $day = $minutes >= 1440 ? $midnight->modify('+1 day') : $midnight;
        $minutes = $minutes % 1440;
        return $day->setTime(intdiv($minutes, 60), $minutes % 60)->getTimestamp();
    }
}
