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
 * English strings for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['error:alreadybookedtoday'] = 'You already have a booking of this space on this day. Cancel it to choose another range.';
$string['error:cannotcancel'] = 'You cannot cancel this booking.';
$string['error:invalidadmin'] = 'Only people who can manage spaces can be managers of a space.';
$string['error:invalidrange'] = 'Choose a range of consecutive free slots of one day.';
$string['error:noadmins'] = 'Choose at least one manager for the space.';
$string['error:notactive'] = 'This booking has already been cancelled.';
$string['error:rangeinpast'] = 'This range has already started. Choose a later one.';
$string['error:slottaken'] = 'Somebody has just booked part of this range. Choose another one.';
$string['error:spacenotavailable'] = 'This space is not available for booking.';
$string['event:bookingcancelled'] = 'Booking cancelled';
$string['event:bookingcreated'] = 'Booking created';
$string['event:spacecreated'] = 'Space created';
$string['event:spacedeleted'] = 'Space deleted';
$string['event:spaceupdated'] = 'Space updated';
$string['managespaces'] = 'Manage spaces';
$string['pluginname'] = 'Space booking';
$string['tipbooking:book'] = 'Book spaces';
$string['tipbooking:manage'] = 'Manage spaces and their bookings';
$string['tipbooking:view'] = 'View the spaces available for booking';
