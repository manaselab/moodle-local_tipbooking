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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use core_date;
use DateTimeImmutable;
use DateTimeZone;
use local_tipbooking\booking_manager;
use local_tipbooking\models\space;
use local_tipbooking\slot_calculator;

/**
 * Page of one space: details, the week of the chosen day and the slots of that day.
 *
 * The first day is drawn here; the script of the page (local_tipbooking/space_booking)
 * draws the other days of the week with the same template.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_page implements renderable, templatable {
    /** @var int Days looked ahead for the first day with a free slot. */
    public const DAYS_SEARCHED = 60;

    /** @var slot_calculator Slots of the space. */
    private slot_calculator $calculator;

    /** @var int The current moment. */
    private int $now;

    /** @var string Today, as Y-m-d in the time zone of the space. */
    private string $today;

    /** @var string The day shown, as Y-m-d in the time zone of the space. */
    private string $date;

    /**
     * Prepares the page.
     *
     * @param space    $space     The space.
     * @param string   $requested Day asked for in the address, as Y-m-d; '' for the default one.
     * @param int      $userid    Identifier of the reader.
     * @param bool     $canmanage Whether the reader manages the space.
     * @param bool     $canbook   Whether the reader can book.
     * @param url|null $imageurl  Image of the space, if any.
     * @param int|null $now       The current moment; null for now.
     */
    public function __construct(
        /** @var space The space. */
        private readonly space $space,
        string $requested,
        /** @var int Identifier of the reader. */
        private readonly int $userid,
        /** @var bool Whether the reader manages the space. */
        private readonly bool $canmanage,
        /** @var bool Whether the reader can book. */
        private readonly bool $canbook,
        /** @var url|null Image of the space, if any. */
        private readonly ?url $imageurl,
        ?int $now = null,
    ) {
        $this->now = $now ?? time();
        $this->calculator = new slot_calculator($space);
        $this->today = $this->calculator->date_of($this->now);
        $this->date = self::is_date($requested) ? $requested : $this->first_open_day();
    }

    /**
     * The day shown.
     *
     * @return string The day, as Y-m-d in the time zone of the space.
     */
    public function get_date(): string {
        return $this->date;
    }

    /**
     * Data for the template local_tipbooking/space_page.
     *
     * @param  renderer_base $output The renderer.
     * @return array                 The context.
     */
    public function export_for_template(renderer_base $output): array {
        $zone = $this->space->get_timezone();
        $week = $this->week();
        $chosen = $week['statuses'][$this->date]
            ?? booking_manager::get_day_status($this->space, $this->date, $this->userid, $this->now);
        $ended = slot_calculator::day_key($this->calculator->date_of($this->space->dateto))
            < slot_calculator::day_key($this->today);

        $usertimezone = core_date::get_user_timezone();
        $moment = new DateTimeImmutable('@' . $this->midnight($this->date));
        $spaceoffset = $zone->getOffset($moment);
        $useroffset = (new DateTimeZone($usertimezone))->getOffset($moment);
        $pageurl = new url('/local/tipbooking/space.php', ['id' => $this->space->id]);

        return [
            'id' => $this->space->id,
            'name' => space_summary::name($this->space),
            'description' => space_summary::description($this->space),
            'hasimage' => $this->imageurl !== null,
            'imageurl' => $this->imageurl?->out(false) ?? '',
            'period' => space_summary::period($this->space),
            'hours' => space_summary::hours($this->space),
            'slot' => space_summary::slot($this->space),
            'days' => space_summary::days($this->space),
            'canmanage' => $this->canmanage,
            'editurl' => (new url('/local/tipbooking/edit.php', ['id' => $this->space->id]))->out(false),
            'ended' => $ended,
            'endedlabel' => get_string('space:ended', 'local_tipbooking', userdate(
                $this->space->dateto,
                get_string('strftimedate', 'langconfig'),
                $zone->getName()
            )),
            'week' => $week['days'],
            'prevurl' => (new url($pageurl, ['date' => self::add_days($week['monday'], -7)]))->out(false),
            'nexturl' => (new url($pageurl, ['date' => self::add_days($week['monday'], 7)]))->out(false),
            'todayurl' => $pageurl->out(false),
            'zonelabel' => get_string('space:timesin', 'local_tipbooking', (object) [
                'zone' => $zone->getName(),
                'offset' => $moment->setTimezone($zone)->format('P'),
            ]),
            'showusertime' => $spaceoffset !== $useroffset,
            'usertimelabel' => get_string('space:usertime', 'local_tipbooking', $usertimezone),
            'grid' => self::grid_context($this->space, $this->date, $chosen, $this->day_label($this->date), $this->canbook),
            'config' => json_encode([
                'spaceid' => $this->space->id,
                'spacename' => space_summary::name($this->space),
                'timezone' => $zone->getName(),
                'usertimezone' => $usertimezone,
                'canbook' => $this->canbook,
                'date' => $this->date,
                'pageurl' => $pageurl->out(false),
            ]),
        ];
    }

    /**
     * Context of the template local_tipbooking/slot_grid for one day.
     *
     * The script of the page builds the same context from local_tipbooking_get_day_slots;
     * both must stay alike.
     *
     * @param  space  $space    The space.
     * @param  string $date     The day, as Y-m-d.
     * @param  array  $status   The day as booking_manager::get_day_status() returns it.
     * @param  string $daylabel The day, ready to print.
     * @param  bool   $canbook  Whether the reader can book.
     * @return array            The context.
     */
    public static function grid_context(space $space, string $date, array $status, string $daylabel, bool $canbook): array {
        $zone = $space->get_timezone()->getName();
        $slots = [];
        $free = 0;
        foreach ($status['slots'] as $index => $slot) {
            $isfree = $slot['status'] === booking_manager::SLOT_FREE;
            $free += $isfree ? 1 : 0;
            $slots[] = [
                'index' => $index,
                'start' => $slot['start'],
                'end' => $slot['end'],
                'label' => self::range_label($slot['start'], $slot['end'], $zone),
                'free' => $isfree && $canbook,
                'busy' => $slot['status'] === booking_manager::SLOT_BUSY,
                'mine' => $slot['status'] === booking_manager::SLOT_MINE,
                'past' => $slot['status'] === booking_manager::SLOT_PAST,
                'blocked' => $slot['status'] === booking_manager::SLOT_BLOCKED || ($isfree && !$canbook),
            ];
        }

        $mybooking = $status['mybooking'];
        return [
            'date' => $date,
            'daylabel' => $daylabel,
            'hasslots' => $slots !== [],
            'closed' => $slots === [],
            'full' => $slots !== [] && $free === 0 && $mybooking === null,
            'hasmybooking' => $mybooking !== null,
            'mybooking' => $mybooking === null ? null : [
                'id' => $mybooking->id,
                'label' => self::range_label($mybooking->timestart, $mybooking->timeend, $zone),
            ],
            'slots' => $slots,
        ];
    }

    /**
     * The week of the chosen day, from Monday, with the state of each day.
     *
     * @return array ['monday' => Y-m-d, 'days' => list for the template, 'statuses' => Y-m-d => day status].
     */
    private function week(): array {
        $weekday = (int) DateTimeImmutable::createFromFormat('!Y-m-d', $this->date, new DateTimeZone('UTC'))->format('N');
        $monday = self::add_days($this->date, 1 - $weekday);
        $todaykey = slot_calculator::day_key($this->today);

        $days = [];
        $statuses = [];
        for ($i = 0; $i < 7; $i++) {
            $date = self::add_days($monday, $i);
            $status = booking_manager::get_day_status($this->space, $date, $this->userid, $this->now);
            $statuses[$date] = $status;

            $free = count(array_filter($status['slots'], fn($slot) => $slot['status'] === booking_manager::SLOT_FREE));
            $past = slot_calculator::day_key($date) < $todaykey;
            $closed = !$past && $status['slots'] === [];
            $mine = $status['mybooking'] !== null;
            $full = !$past && !$closed && !$mine && $free === 0;

            if ($past) {
                $sub = get_string('day:past', 'local_tipbooking');
            } else if ($closed) {
                $sub = get_string('day:closed', 'local_tipbooking');
            } else if ($mine) {
                $sub = get_string('day:mine', 'local_tipbooking');
            } else if ($full) {
                $sub = get_string('day:full', 'local_tipbooking');
            } else {
                $sub = get_string('day:free', 'local_tipbooking', $free);
            }

            $midnight = $this->midnight($date);
            $days[] = [
                'date' => $date,
                'dow' => get_string('form:weekdayshort' . ($i + 1), 'local_tipbooking'),
                'num' => (int) substr($date, 8, 2),
                'sub' => $sub,
                'daylabel' => $this->day_label($date),
                'selected' => $date === $this->date,
                'today' => $date === $this->today,
                'disabled' => $past || $closed,
                'past' => $past,
                'closed' => $closed,
                'mine' => $mine,
                'full' => $full,
                'freecount' => $free,
                'timestamp' => $midnight,
            ];
        }

        return ['monday' => $monday, 'days' => $days, 'statuses' => $statuses];
    }

    /**
     * Today or, if it has no slot left, the first later day with one.
     *
     * Days before the bookable period are skipped. When no day in the next DAYS_SEARCHED
     * has a slot left, the first of them is shown, with its own empty state.
     *
     * @return string The day, as Y-m-d.
     */
    private function first_open_day(): string {
        $start = $this->today;
        $first = $this->calculator->date_of($this->space->datefrom);
        if (slot_calculator::day_key($first) > slot_calculator::day_key($start)) {
            $start = $first;
        }

        for ($i = 0; $i < self::DAYS_SEARCHED; $i++) {
            $date = self::add_days($start, $i);
            foreach ($this->calculator->day_slots($date) as $slot) {
                if ($slot['start'] > $this->now) {
                    return $date;
                }
            }
        }

        return $start;
    }

    /**
     * A day ready to print, in the language of the reader.
     *
     * @param  string $date The day, as Y-m-d.
     * @return string       "Thursday, 3 December 2026".
     */
    private function day_label(string $date): string {
        return userdate(
            $this->midnight($date),
            get_string('strftimedaydate', 'langconfig'),
            $this->space->get_timezone()->getName()
        );
    }

    /**
     * Midnight of a day in the time zone of the space.
     *
     * @param  string $date The day, as Y-m-d.
     * @return int          The timestamp.
     */
    private function midnight(string $date): int {
        return DateTimeImmutable::createFromFormat('!Y-m-d', $date, $this->space->get_timezone())->getTimestamp();
    }

    /**
     * A range of time as HH:MM–HH:MM in a time zone.
     *
     * @param  int    $start Start of the range.
     * @param  int    $end   End of the range.
     * @param  string $zone  The time zone.
     * @return string        "10:00–12:00".
     */
    public static function range_label(int $start, int $end, string $zone): string {
        return userdate($start, '%H:%M', $zone) . '–' . userdate($end, '%H:%M', $zone);
    }

    /**
     * Whether a text is a real day written as Y-m-d.
     *
     * @param  string $date The text.
     * @return bool         True for a valid day.
     */
    private static function is_date(string $date): bool {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'));
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }

    /**
     * Moves a day some days forward or back.
     *
     * @param  string $date The day, as Y-m-d.
     * @param  int    $days Days to add; negative to go back.
     * @return string       The new day, as Y-m-d.
     */
    private static function add_days(string $date, int $days): string {
        return DateTimeImmutable::createFromFormat('!Y-m-d', $date, new DateTimeZone('UTC'))
            ->modify(sprintf('%+d days', $days))
            ->format('Y-m-d');
    }
}
