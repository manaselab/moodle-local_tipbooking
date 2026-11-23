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
 * External function to book a range of slots.
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
use local_tipbooking\exception\already_booked_today;
use local_tipbooking\exception\slot_taken;
use moodle_exception;

/**
 * Books a range of consecutive slots of one day for the current user.
 *
 * The two answers people meet in normal use, a range somebody else has just taken and a
 * second booking of the same day, come back as a status with its message for the page to
 * show; anything else is an error.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class book_range extends external_api {
    /** @var string Status of a booking made. */
    public const STATUS_BOOKED = 'booked';

    /** @var string Status of a range somebody else holds. */
    public const STATUS_SLOT_TAKEN = 'slottaken';

    /** @var string Status of a second booking of the same space and day. */
    public const STATUS_ALREADY_BOOKED = 'alreadybooked';

    /**
     * Parameters of the function.
     *
     * @return external_function_parameters The parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'spaceid' => new external_value(PARAM_INT, 'Identifier of the space'),
            'timestart' => new external_value(PARAM_INT, 'Start of the first slot'),
            'timeend' => new external_value(PARAM_INT, 'End of the last slot'),
        ]);
    }

    /**
     * Books the range.
     *
     * @param  int   $spaceid   Identifier of the space.
     * @param  int   $timestart Start of the first slot.
     * @param  int   $timeend   End of the last slot.
     * @return array            The status, the booking and the message to show.
     * @throws moodle_exception When the space is not available or the range is not valid.
     */
    public static function execute(int $spaceid, int $timestart, int $timeend): array {
        global $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'spaceid' => $spaceid,
            'timestart' => $timestart,
            'timeend' => $timeend,
        ]);
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/tipbooking:book', $context);

        try {
            $booking = booking_manager::book($params['spaceid'], (int) $USER->id, $params['timestart'], $params['timeend']);
        } catch (slot_taken $e) {
            return ['status' => self::STATUS_SLOT_TAKEN, 'bookingid' => 0, 'message' => $e->getMessage()];
        } catch (already_booked_today $e) {
            return ['status' => self::STATUS_ALREADY_BOOKED, 'bookingid' => 0, 'message' => $e->getMessage()];
        }

        return [
            'status' => self::STATUS_BOOKED,
            'bookingid' => $booking->id,
            'message' => get_string('bookingconfirmed', 'local_tipbooking'),
        ];
    }

    /**
     * Structure of the answer.
     *
     * @return external_single_structure The structure.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_ALPHA, 'booked, slottaken or alreadybooked'),
            'bookingid' => new external_value(PARAM_INT, 'Identifier of the booking; 0 when none was made'),
            'message' => new external_value(PARAM_TEXT, 'Message to show'),
        ]);
    }
}
