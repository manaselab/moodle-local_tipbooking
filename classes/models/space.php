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
 * Space domain model.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\models;

use core_date;
use DateTimeZone;
use stdClass;

/**
 * A bookable space: a room, a lab or a piece of equipment.
 *
 * Opening hours are minutes from midnight and the bookable period is two midnights, all of
 * them read in the time zone of the space, so a space in Bishkek opens at 08:30 in Bishkek
 * whatever the time zone of the site or of the reader.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space {
    /** @var string Database table name (without prefix). */
    public const TABLE = 'local_tipbooking_space';

    /** @var int Bit mask of Monday to Friday, the default bookable days. */
    public const WEEKDAYS_DEFAULT = 31;

    /** @var int Bit mask of every day of the week. */
    public const WEEKDAYS_ALL = 127;

    /** @var int Record id (0 when not persisted yet). */
    public int $id = 0;

    /** @var string Name; may carry multilang tags. */
    public string $name = '';

    /** @var string Description, in $descriptionformat. */
    public string $description = '';

    /** @var int Format of the description; 1 is FORMAT_HTML, which core defines as a string. */
    public int $descriptionformat = 1;

    /** @var string Time zone of the opening hours; empty means the time zone of the server. */
    public string $timezone = '';

    /** @var int Midnight of the first bookable day, in the time zone of the space. */
    public int $datefrom = 0;

    /** @var int Midnight of the last bookable day, in the time zone of the space. */
    public int $dateto = 0;

    /** @var int Opening time, in minutes from midnight. */
    public int $hourfrom = 480;

    /** @var int Closing time, in minutes from midnight. */
    public int $hourto = 1080;

    /** @var int Length of each slot, in minutes. */
    public int $slotduration = 60;

    /** @var int Bookable weekdays: Monday 1, Tuesday 2 ... Sunday 64. */
    public int $weekdays = self::WEEKDAYS_DEFAULT;

    /** @var int Whether the space is shown in the showcase. */
    public int $visible = 1;

    /** @var int User who last changed the space. */
    public int $usermodified = 0;

    /** @var int Creation timestamp. */
    public int $timecreated = 0;

    /** @var int Last modification timestamp. */
    public int $timemodified = 0;

    /**
     * Builds a model instance from a database record.
     *
     * @param  stdClass $record Database record of the spaces table.
     * @return self             The hydrated model.
     */
    public static function from_record(stdClass $record): self {
        $space = new self();
        $space->id = (int) $record->id;
        $space->name = (string) $record->name;
        $space->description = (string) ($record->description ?? '');
        $space->descriptionformat = (int) $record->descriptionformat;
        $space->timezone = (string) $record->timezone;
        $space->datefrom = (int) $record->datefrom;
        $space->dateto = (int) $record->dateto;
        $space->hourfrom = (int) $record->hourfrom;
        $space->hourto = (int) $record->hourto;
        $space->slotduration = (int) $record->slotduration;
        $space->weekdays = (int) $record->weekdays;
        $space->visible = (int) $record->visible;
        $space->usermodified = (int) $record->usermodified;
        $space->timecreated = (int) $record->timecreated;
        $space->timemodified = (int) $record->timemodified;
        return $space;
    }

    /**
     * Converts the model to a database record.
     *
     * @return stdClass The record, without id when the space is not persisted yet.
     */
    public function to_record(): stdClass {
        $record = (object) [
            'name' => $this->name,
            'description' => $this->description,
            'descriptionformat' => $this->descriptionformat,
            'timezone' => $this->timezone,
            'datefrom' => $this->datefrom,
            'dateto' => $this->dateto,
            'hourfrom' => $this->hourfrom,
            'hourto' => $this->hourto,
            'slotduration' => $this->slotduration,
            'weekdays' => $this->weekdays,
            'visible' => $this->visible,
            'usermodified' => $this->usermodified,
            'timecreated' => $this->timecreated,
            'timemodified' => $this->timemodified,
        ];
        if ($this->id) {
            $record->id = $this->id;
        }
        return $record;
    }

    /**
     * The time zone the opening hours and the bookable period are read in.
     *
     * @return DateTimeZone The time zone of the space, or of the server when it has none.
     */
    public function get_timezone(): DateTimeZone {
        $name = $this->timezone !== '' ? $this->timezone : core_date::get_server_timezone();
        return new DateTimeZone(core_date::normalise_timezone($name));
    }

    /**
     * Whether a day of the week is bookable.
     *
     * @param  int  $isoweekday Day of the week, 1 for Monday to 7 for Sunday.
     * @return bool             True when the mask of the space includes it.
     */
    public function is_weekday_bookable(int $isoweekday): bool {
        return ($this->weekdays & (1 << ($isoweekday - 1))) !== 0;
    }
}
