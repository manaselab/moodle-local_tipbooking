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
 * Behat data generator for local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_tipbooking\models\space;
use local_tipbooking\slot_calculator;

/**
 * Behat data generator exposing spaces and bookings as creatable entities.
 *
 * Usage in features:
 *   Given the following "local_tipbooking > spaces" exist:
 *     | name  | admins   | hourfrom | hourto | slothours | startsin |
 *     | Lab 1 | manager1 | 09:00    | 18:00  | 1         | 3        |
 *   And the following "local_tipbooking > bookings" exist:
 *     | space | user     | daysahead | from  | to    |
 *     | Lab 1 | student1 | 3         | 09:00 | 11:00 |
 *
 * Times are read in the time zone of the space (Asia/Bishkek unless "timezone" is given),
 * so the slots a scenario clicks keep their labels whatever the time zone of the runner.
 * "startsin" is the first bookable day, in days from today; "daysahead" is the day of a
 * booking in the same way.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_local_tipbooking_generator extends behat_generator_base {
    /**
     * Declares the entities this component can generate in Behat.
     *
     * @return array The entity definitions.
     */
    protected function get_creatable_entities(): array {
        return [
            'spaces' => [
                'singular' => 'space',
                'datagenerator' => 'space',
                'required' => ['name'],
            ],
            'bookings' => [
                'singular' => 'booking',
                'datagenerator' => 'booking',
                'required' => ['space', 'user', 'from', 'to'],
            ],
        ];
    }

    /**
     * Turns the readable columns of a space into the fields of the data generator.
     *
     * @param  array $data Row of the table: admins (usernames, comma separated), hourfrom and
     *                     hourto (HH:MM), slothours, startsin (days), timezone, weekdays.
     * @return array       The record for local_tipbooking_generator::create_space().
     */
    protected function preprocess_space(array $data): array {
        global $DB;

        if (isset($data['admins'])) {
            $data['admins'] = array_map(
                fn(string $username): int => (int) $DB->get_field('user', 'id', ['username' => trim($username)], MUST_EXIST),
                explode(',', $data['admins'])
            );
        }
        foreach (['hourfrom', 'hourto'] as $field) {
            if (isset($data[$field])) {
                $data[$field] = self::minutes($data[$field]);
            }
        }
        if (isset($data['slothours'])) {
            $data['slotduration'] = (int) round((float) $data['slothours'] * 60);
            unset($data['slothours']);
        }
        if (isset($data['startsin'])) {
            $zone = new DateTimeZone($data['timezone'] ?? 'Asia/Bishkek');
            $today = (new DateTimeImmutable('now', $zone))->setTime(0, 0);
            $data['datefrom'] = $today->modify('+' . (int) $data['startsin'] . ' days')->getTimestamp();
            $data['dateto'] = $today->modify('+' . ((int) $data['startsin'] + 60) . ' days')->getTimestamp();
            unset($data['startsin']);
        }
        if (isset($data['weekdays'])) {
            $data['weekdays'] = (int) $data['weekdays'];
        }

        return $data;
    }

    /**
     * Turns the readable columns of a booking into the fields of the data generator.
     *
     * @param  array $data Row of the table: space (name), user (username), daysahead, from and
     *                     to (HH:MM, in the time zone of the space).
     * @return array       The record for local_tipbooking_generator::create_booking().
     */
    protected function preprocess_booking(array $data): array {
        global $DB;

        $record = $DB->get_record(space::TABLE, ['name' => $data['space']], '*', MUST_EXIST);
        $space = space::from_record($record);
        $day = (new DateTimeImmutable('now', $space->get_timezone()))
            ->modify('+' . (int) ($data['daysahead'] ?? 1) . ' days')
            ->format('Y-m-d');
        $midnight = DateTimeImmutable::createFromFormat('!Y-m-d', $day, $space->get_timezone());

        $timestart = $midnight->modify('+' . self::minutes($data['from']) . ' minutes')->getTimestamp();
        $timeend = $midnight->modify('+' . self::minutes($data['to']) . ' minutes')->getTimestamp();
        if ((new slot_calculator($space))->range_slots($timestart, $timeend) === null) {
            throw new Exception("{$data['from']}–{$data['to']} on {$day} is not a range of slots of '{$data['space']}'");
        }

        return [
            'spaceid' => $space->id,
            'userid' => (int) $DB->get_field('user', 'id', ['username' => $data['user']], MUST_EXIST),
            'timestart' => $timestart,
            'timeend' => $timeend,
        ];
    }

    /**
     * Minutes from midnight of a time written as HH:MM.
     *
     * @param  string $time The time.
     * @return int          Minutes from midnight.
     */
    private static function minutes(string $time): int {
        [$hours, $minutes] = array_map('intval', explode(':', trim($time)) + [1 => 0]);
        return $hours * 60 + $minutes;
    }
}
