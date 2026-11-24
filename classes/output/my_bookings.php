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
use DateTimeImmutable;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\slot_calculator;

/**
 * The bookings of the reader, upcoming or past, grouped by day.
 *
 * Everything it shows arrives through the constructor; it reads nothing by itself.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class my_bookings implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param booking[] $bookings  Bookings of the current page, in the order to show.
     * @param space[]   $spaces    Space id => space, for every booking.
     * @param array     $imageurls Space id => address of its image, or null.
     * @param bool      $upcoming  Whether the list is of upcoming bookings.
     * @param string    $tabs      The tabs, already rendered.
     * @param string    $pagingbar The paging bar, already rendered.
     * @param int|null  $now       The current moment; null for now.
     */
    public function __construct(
        /** @var booking[] Bookings of the current page. */
        private readonly array $bookings,
        /** @var space[] Space id => space. */
        private readonly array $spaces,
        /** @var array Space id => address of its image, or null. */
        private readonly array $imageurls,
        /** @var bool Whether the list is of upcoming bookings. */
        private readonly bool $upcoming,
        /** @var string The tabs, already rendered. */
        private readonly string $tabs,
        /** @var string The paging bar, already rendered. */
        private readonly string $pagingbar,
        /** @var int|null The current moment; null for now. */
        private readonly ?int $now = null,
    ) {
    }

    /**
     * Data for the template local_tipbooking/my_bookings.
     *
     * @param  renderer_base $output The renderer.
     * @return array                 The context.
     */
    public function export_for_template(renderer_base $output): array {
        $now = $this->now ?? time();
        $groups = [];
        foreach ($this->bookings as $booking) {
            $space = $this->spaces[$booking->spaceid];
            if (!isset($groups[$booking->day])) {
                $groups[$booking->day] = ['title' => $this->day_title($booking, $space, $now), 'items' => []];
            }
            $groups[$booking->day]['items'][] = $this->item($booking, $space, $now);
        }

        return [
            'tabs' => $this->tabs,
            'groups' => array_values($groups),
            'hasbookings' => $groups !== [],
            'upcoming' => $this->upcoming,
            'catalogueurl' => (new url('/local/tipbooking/index.php'))->out(false),
            'pagingbar' => $this->pagingbar,
        ];
    }

    /**
     * One booking of the list.
     *
     * @param  booking $booking The booking.
     * @param  space   $space   Its space.
     * @param  int     $now     The current moment.
     * @return array            The item for the template.
     */
    private function item(booking $booking, space $space, int $now): array {
        $zone = $space->get_timezone()->getName();
        $active = $booking->is_active();
        if (!$active) {
            $status = 'cancelled';
        } else if ($booking->timeend <= $now) {
            $status = 'finished';
        } else if ($booking->timestart <= $now) {
            $status = 'inprogress';
        } else {
            $status = 'confirmed';
        }

        $date = (new slot_calculator($space))->date_of($booking->timestart);
        $imageurl = $this->imageurls[$space->id] ?? null;
        $range = space_page::range_label($booking->timestart, $booking->timeend, $zone);

        return [
            'id' => $booking->id,
            'spacename' => space_summary::name($space),
            'spaceurl' => $space->visible
                ? (new url('/local/tipbooking/space.php', ['id' => $space->id, 'date' => $date]))->out(false)
                : '',
            'hasimage' => $imageurl !== null,
            'imageurl' => $imageurl?->out(false) ?? '',
            'range' => $range,
            'timezone' => $zone,
            'duration' => self::duration($booking->timeend - $booking->timestart),
            'daylabel' => userdate($booking->timestart, get_string('strftimedaydate', 'langconfig'), $zone),
            'status' => get_string('mybookings:status' . $status, 'local_tipbooking'),
            'confirmed' => $status === 'confirmed',
            'inprogress' => $status === 'inprogress',
            'cancancel' => $status === 'confirmed',
        ];
    }

    /**
     * Title of the group of a day: "Today", "Tomorrow" or the date.
     *
     * The day is the one of the booking in the time zone of its space.
     *
     * @param  booking $booking The first booking of the day.
     * @param  space   $space   Its space.
     * @param  int     $now     The current moment.
     * @return string           The title.
     */
    private function day_title(booking $booking, space $space, int $now): string {
        $today = new DateTimeImmutable((new slot_calculator($space))->date_of($now));
        $day = DateTimeImmutable::createFromFormat('!Ymd', (string) $booking->day);
        $diff = (int) $today->diff($day)->format('%r%a');
        if ($diff === 0) {
            return get_string('today', 'calendar');
        }
        if ($diff === 1) {
            return get_string('tomorrow', 'calendar');
        }
        if ($diff === -1) {
            return get_string('yesterday', 'calendar');
        }
        return userdate($booking->timestart, get_string('strftimedaydate', 'langconfig'), $space->get_timezone()->getName());
    }

    /**
     * Length of a booking, as "3 h" or "1 h 30 min".
     *
     * @param  int    $seconds Length in seconds.
     * @return string          The length.
     */
    public static function duration(int $seconds): string {
        $minutes = intdiv($seconds, 60);
        $parts = [];
        if ($minutes >= 60) {
            $parts[] = get_string('duration:hours', 'local_tipbooking', intdiv($minutes, 60));
        }
        if ($minutes % 60) {
            $parts[] = get_string('duration:minutes', 'local_tipbooking', $minutes % 60);
        }
        return implode(' ', $parts);
    }
}
