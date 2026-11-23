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
 * Message providers of local_tipbooking.
 *
 * Every provider is on by default for the web and email; each person can change it in
 * their notification preferences.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$defaults = [
    'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
    'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
];

$messageproviders = [
    // To the person who booked: the booking is confirmed.
    'bookingconfirmed' => ['defaults' => $defaults],

    // To the person who booked: the booking was cancelled, by them or by a manager.
    'bookingcancelled' => ['defaults' => $defaults],

    // To the managers of the space: somebody booked it.
    'adminbookingcreated' => [
        'capability' => 'local/tipbooking:manage',
        'defaults' => $defaults,
    ],

    // To the managers of the space: a booking of it was cancelled.
    'adminbookingcancelled' => [
        'capability' => 'local/tipbooking:manage',
        'defaults' => $defaults,
    ],
];
