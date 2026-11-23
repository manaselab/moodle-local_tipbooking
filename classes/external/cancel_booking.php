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
 * External function to cancel a booking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use local_tipbooking\booking_manager;
use moodle_exception;

/**
 * Cancels a booking: the holder's own before it starts, or any of a space the user manages.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cancel_booking extends external_api {
    /**
     * Parameters of the function.
     *
     * @return external_function_parameters The parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'bookingid' => new external_value(PARAM_INT, 'Identifier of the booking'),
        ]);
    }

    /**
     * Cancels the booking.
     *
     * @param  int   $bookingid Identifier of the booking.
     * @return array            The status and the message to show.
     * @throws moodle_exception When the booking is not active or the user cannot cancel it.
     */
    public static function execute(int $bookingid): array {
        global $USER;

        ['bookingid' => $bookingid] = self::validate_parameters(self::execute_parameters(), ['bookingid' => $bookingid]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/tipbooking:view', $context);

        booking_manager::cancel($bookingid, (int) $USER->id);

        return ['status' => 'cancelled', 'message' => get_string('bookingcancelled', 'local_tipbooking')];
    }

    /**
     * Structure of the answer.
     *
     * @return external_single_structure The structure.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'cancelled'),
            'message' => new external_value(PARAM_TEXT, 'Message to show'),
        ]);
    }
}
