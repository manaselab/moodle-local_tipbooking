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
 * Data generator for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_tipbooking\booking_manager;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\space_manager;

/**
 * Data generator class for local_tipbooking tests.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_tipbooking_generator extends component_generator_base {
    /** @var int Counter for generated spaces. */
    protected int $spacecount = 0;

    /**
     * Resets the generator counters.
     *
     * @return void
     */
    public function reset() {
        $this->spacecount = 0;
    }

    /**
     * Creates a space.
     *
     * By default the space is in Bishkek, open 08:30–17:30 in 1-hour slots every day of the
     * week, bookable from ten days ago to sixty days ahead, and managed by the site admin.
     *
     * @param  array|null $record Optional overrides of the fields of the space, plus 'admins' (user ids).
     * @return space              The created space.
     */
    public function create_space(?array $record = null): space {
        $this->spacecount++;
        $record = (array) $record;

        $space = new space();
        $space->name = $record['name'] ?? 'Space ' . $this->spacecount;
        $space->description = $record['description'] ?? '';
        $space->timezone = $record['timezone'] ?? 'Asia/Bishkek';
        $space->hourfrom = $record['hourfrom'] ?? 510;
        $space->hourto = $record['hourto'] ?? 1050;
        $space->slotduration = $record['slotduration'] ?? 60;
        $space->weekdays = $record['weekdays'] ?? space::WEEKDAYS_ALL;
        $space->visible = $record['visible'] ?? 1;

        $zone = $space->get_timezone();
        $today = (new DateTimeImmutable('now', $zone))->setTime(0, 0);
        $space->datefrom = $record['datefrom'] ?? $today->modify('-10 days')->getTimestamp();
        $space->dateto = $record['dateto'] ?? $today->modify('+60 days')->getTimestamp();

        $admins = $record['admins'] ?? [(int) get_admin()->id];

        return space_manager::create($space, $admins);
    }

    /**
     * Books a range of a space for a person.
     *
     * @param  array   $record 'spaceid', 'userid', 'timestart' and 'timeend'.
     * @return booking         The booking.
     */
    public function create_booking(array $record): booking {
        return booking_manager::book(
            (int) $record['spaceid'],
            (int) $record['userid'],
            (int) $record['timestart'],
            (int) $record['timeend']
        );
    }

    /**
     * A day some days from today, in the time zone of a space.
     *
     * @param  space  $space The space.
     * @param  int    $days  Days from today.
     * @return string        The day, as Y-m-d.
     */
    public function day_ahead(space $space, int $days): string {
        return (new DateTimeImmutable('now', $space->get_timezone()))
            ->modify(($days >= 0 ? '+' : '') . $days . ' days')
            ->format('Y-m-d');
    }
}
