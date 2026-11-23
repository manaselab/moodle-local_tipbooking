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
 * External function to read the slots of a day of a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use dml_missing_record_exception;
use local_tipbooking\booking_manager;
use local_tipbooking\space_manager;
use moodle_exception;
use require_login_exception;

/**
 * Returns the slots of one day of a space as the current user sees them.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_day_slots extends external_api {
    /**
     * Parameters of the function.
     *
     * @return external_function_parameters The parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'spaceid' => new external_value(PARAM_INT, 'Identifier of the space'),
            'date' => new external_value(PARAM_ALPHANUMEXT, 'Day, as YYYY-MM-DD in the time zone of the space'),
        ]);
    }

    /**
     * Reads the slots of the day.
     *
     * @param  int    $spaceid Identifier of the space.
     * @param  string $date    Day, as YYYY-MM-DD in the time zone of the space.
     * @return array           The slots and the booking of the user that day, if any.
     * @throws moodle_exception When the space is not available to the user.
     * @throws require_login_exception For guests.
     */
    public static function execute(int $spaceid, string $date): array {
        global $USER;

        ['spaceid' => $spaceid, 'date' => $date] = self::validate_parameters(
            self::execute_parameters(),
            ['spaceid' => $spaceid, 'date' => $date]
        );
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/tipbooking:view', $context);
        if (isguestuser()) {
            throw new require_login_exception('Guests cannot book spaces.');
        }

        try {
            $space = space_manager::get($spaceid);
        } catch (dml_missing_record_exception $e) {
            throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
        }
        if (!$space->visible && !space_manager::can_manage_space($spaceid, (int) $USER->id)) {
            throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
        }

        $status = booking_manager::get_day_status($space, $date, (int) $USER->id);
        $mybooking = $status['mybooking'];
        $free = count(array_filter($status['slots'], fn($slot) => $slot['status'] === booking_manager::SLOT_FREE));

        $result = [
            'date' => $date,
            'slots' => $status['slots'],
            'freecount' => $free,
        ];
        if ($mybooking !== null) {
            $result['mybooking'] = [
                'id' => $mybooking->id,
                'timestart' => $mybooking->timestart,
                'timeend' => $mybooking->timeend,
            ];
        }

        return $result;
    }

    /**
     * Structure of the answer.
     *
     * @return external_single_structure The structure.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'date' => new external_value(PARAM_ALPHANUMEXT, 'The day'),
            'slots' => new external_multiple_structure(new external_single_structure([
                'start' => new external_value(PARAM_INT, 'Start of the slot'),
                'end' => new external_value(PARAM_INT, 'End of the slot'),
                'status' => new external_value(PARAM_ALPHA, 'free, busy, mine, past or blocked'),
            ])),
            'freecount' => new external_value(PARAM_INT, 'How many slots are free'),
            'mybooking' => new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Identifier of the booking'),
                'timestart' => new external_value(PARAM_INT, 'Start of the booking'),
                'timeend' => new external_value(PARAM_INT, 'End of the booking'),
            ], 'Booking of the user that day', VALUE_OPTIONAL),
        ]);
    }
}
