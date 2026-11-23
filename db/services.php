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
 * External functions of local_tipbooking.
 *
 * They serve the pages of the plugin through AJAX only; no external service exposes them.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_tipbooking_get_day_slots' => [
        'classname' => \local_tipbooking\external\get_day_slots::class,
        'description' => 'Returns the slots of one day of a space as the current user sees them.',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'local/tipbooking:view',
    ],
    'local_tipbooking_book_range' => [
        'classname' => \local_tipbooking\external\book_range::class,
        'description' => 'Books a range of consecutive slots of one day for the current user.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'local/tipbooking:book',
    ],
    'local_tipbooking_cancel_booking' => [
        'classname' => \local_tipbooking\external\cancel_booking::class,
        'description' => 'Cancels a booking of the current user, or of a space the user manages.',
        'type' => 'write',
        'ajax' => true,
        'loginrequired' => true,
        'capabilities' => 'local/tipbooking:view',
    ],
];
