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
 * Booking cancelled event.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\event;

use coding_exception;
use core\event\base;
use core\url;

/**
 * Fired when a booking is cancelled, by its holder or by a manager.
 *
 * The related user is who holds the booking; other data carries the space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class booking_cancelled extends base {
    /**
     * Sets the basic properties of the event.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'local_tipbooking_booking';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string The name.
     */
    public static function get_name() {
        return get_string('event:bookingcancelled', 'local_tipbooking');
    }

    /**
     * Returns the description of what happened.
     *
     * @return string The description.
     */
    public function get_description() {
        return "The user with id '{$this->relateduserid}' had the booking of the space with id '{$this->other['spaceid']}' "
            . "cancelled by the user with id '{$this->userid}' (booking with id '{$this->objectid}').";
    }

    /**
     * Returns the page of the space.
     *
     * @return url The page.
     */
    public function get_url() {
        return new url('/local/tipbooking/space.php', ['id' => $this->other['spaceid']]);
    }

    /**
     * Checks that the event carries what it needs.
     *
     * @return void
     * @throws coding_exception When the related user or the space is missing.
     */
    protected function validate_data() {
        parent::validate_data();
        if (!isset($this->relateduserid)) {
            throw new coding_exception('The \'relateduserid\' must be set.');
        }
        if (!isset($this->other['spaceid'])) {
            throw new coding_exception('The \'spaceid\' value must be set in other.');
        }
    }

    /**
     * Mapping of the object for backup and restore.
     *
     * @return array The mapping.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'local_tipbooking_booking', 'restore' => base::NOT_MAPPED];
    }

    /**
     * Mapping of the other data for backup and restore.
     *
     * @return array The mapping.
     */
    public static function get_other_mapping() {
        return ['spaceid' => ['db' => 'local_tipbooking_space', 'restore' => base::NOT_MAPPED]];
    }
}
