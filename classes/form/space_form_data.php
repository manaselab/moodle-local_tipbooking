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
 * Conversion between the space form and the space model.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\form;

use DateTimeImmutable;
use local_tipbooking\models\space;
use stdClass;

/**
 * Moves the values of a space between its form and its model.
 *
 * The date selectors of the form work in the time zone of whoever edits, while the space
 * keeps its period as midnights of its own time zone. Both sides agree on the calendar day,
 * so the day is what travels between them, never the timestamp.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_form_data {
    /**
     * Values of the form of a space.
     *
     * @param  space    $space    The space; a new one gives the defaults.
     * @param  int[]    $adminids Its managers.
     * @return stdClass           Values for set_data(), before the file and editor preparation.
     */
    public static function to_form(space $space, array $adminids): stdClass {
        $data = (object) [
            'id' => $space->id,
            'name' => $space->name,
            'description' => $space->description,
            'descriptionformat' => $space->descriptionformat,
            'visible' => $space->visible,
            'hourfrom' => $space->hourfrom,
            'hourto' => $space->hourto,
            'slotduration' => $space->slotduration,
            'timezone' => $space->get_timezone()->getName(),
            'admins' => $adminids,
        ];

        if ($space->id) {
            $data->datefrom = self::user_midnight($space, $space->datefrom);
            $data->dateto = self::user_midnight($space, $space->dateto);
        }
        for ($day = 1; $day <= 7; $day++) {
            $data->{'weekday' . $day} = (int) $space->is_weekday_bookable($day);
        }

        return $data;
    }

    /**
     * Applies the submitted values of the form to a space.
     *
     * @param  stdClass $data  Values from get_data(), with the editor already flattened.
     * @param  space    $space The space to change; a new one for a creation.
     * @return space           The same space, changed.
     */
    public static function to_space(stdClass $data, space $space): space {
        $space->name = trim($data->name);
        $space->description = (string) ($data->description ?? '');
        $space->descriptionformat = (int) ($data->descriptionformat ?? FORMAT_HTML);
        $space->visible = (int) !empty($data->visible);
        $space->timezone = (string) $data->timezone;
        $space->hourfrom = (int) $data->hourfrom;
        $space->hourto = (int) $data->hourto;
        $space->slotduration = (int) $data->slotduration;

        $mask = 0;
        for ($day = 1; $day <= 7; $day++) {
            if (!empty($data->{'weekday' . $day})) {
                $mask |= 1 << ($day - 1);
            }
        }
        $space->weekdays = $mask;

        $space->datefrom = self::space_midnight($space, (int) $data->datefrom);
        $space->dateto = self::space_midnight($space, (int) $data->dateto);

        return $space;
    }

    /**
     * Midnight in the time zone of the space of the day a date selector chose.
     *
     * @param  space $space     The space, with its time zone already set.
     * @param  int   $timestamp Value of the date selector, in the time zone of the editor.
     * @return int              Midnight of that day in the time zone of the space.
     */
    private static function space_midnight(space $space, int $timestamp): int {
        $date = userdate($timestamp, '%Y-%m-%d', 99, false);
        return DateTimeImmutable::createFromFormat('!Y-m-d', $date, $space->get_timezone())->getTimestamp();
    }

    /**
     * Value for a date selector of a day stored as a midnight of the space.
     *
     * @param  space $space    The space.
     * @param  int   $midnight Midnight in the time zone of the space.
     * @return int             Midnight of that day in the time zone of the editor.
     */
    private static function user_midnight(space $space, int $midnight): int {
        $day = (new DateTimeImmutable('@' . $midnight))->setTimezone($space->get_timezone());
        return make_timestamp((int) $day->format('Y'), (int) $day->format('n'), (int) $day->format('j'));
    }
}
