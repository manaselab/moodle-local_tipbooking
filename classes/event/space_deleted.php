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
 * Space deleted event.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\event;

use core\event\base;

/**
 * Fired when a space is deleted, with its bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_deleted extends base {
    /**
     * Sets the basic properties of the event.
     *
     * @return void
     */
    protected function init() {
        $this->data['objecttable'] = 'local_tipbooking_space';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string The name.
     */
    public static function get_name() {
        return get_string('event:spacedeleted', 'local_tipbooking');
    }

    /**
     * Returns the description of what happened.
     *
     * @return string The description.
     */
    public function get_description() {
        return "The user with id '{$this->userid}' deleted the space with id '{$this->objectid}'.";
    }

    /**
     * Mapping of the object for backup and restore.
     *
     * @return array The mapping.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'local_tipbooking_space', 'restore' => base::NOT_MAPPED];
    }
}
