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
 * Calendar events of the bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use calendar_event;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\output\space_summary;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Keeps one user event in the core calendar for each active booking.
 *
 * The event belongs to the person who booked, so only they see it. It carries the component of
 * the plugin and the booking id, which is how it is found again: the calendar does not let anyone
 * edit or delete a component event by hand, so it cannot drift from the booking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class calendar_sync {

    /** @var string Component of the events. */
    public const COMPONENT = 'local_tipbooking';

    /** @var string Type of the events: user events are visible only to their owner. */
    public const EVENTTYPE = 'user';

    /**
     * Creates the event of a booking, with the same start and end.
     *
     * @param  booking $booking The booking, already stored.
     * @param  space   $space   Its space.
     * @return int              Identifier of the event.
     */
    public static function create_event(booking $booking, space $space): int {
        $url = new moodle_url('/local/tipbooking/space.php', ['id' => $space->id]);
        $data = (object) [
            'name' => self::name_for($space, $booking->userid),
            'description' => \html_writer::tag('p', \html_writer::link($url, $space->name)),
            'format' => FORMAT_HTML,
            'eventtype' => self::EVENTTYPE,
            'component' => self::COMPONENT,
            'instance' => $booking->id,
            'modulename' => '',
            'courseid' => 0,
            'categoryid' => 0,
            'groupid' => 0,
            'userid' => $booking->userid,
            'timestart' => $booking->timestart,
            'timeduration' => $booking->timeend - $booking->timestart,
            'visible' => 1,
        ];
        // No capability check: the event is created for the person who booked, who may not be
        // the current user (tests, web services) and may lack moodle/calendar:manageownentries.
        return (int) calendar_event::create($data, false)->id;
    }

    /**
     * Name of the space in the language of the person who booked.
     *
     * The calendar prints event names through clean_string(), without filters, so multilang
     * tags would show as they are. Only the owner sees the event, so the name is resolved once,
     * in their language, as the notifier does with the messages.
     *
     * @param  space  $space  The space.
     * @param  int    $userid The person who booked.
     * @return string         The name, as plain text.
     */
    private static function name_for(space $space, int $userid): string {
        $user = \core_user::get_user($userid, 'id, lang');
        $previous = force_current_language($user->lang ?? '');
        try {
            return space_summary::name($space, false);
        } finally {
            force_current_language($previous);
        }
    }

    /**
     * Deletes the event of a booking, if it has one.
     *
     * @param  int  $bookingid Identifier of the booking.
     * @return void
     */
    public static function delete_event(int $bookingid): void {
        global $DB;

        $ids = $DB->get_fieldset_select(
            'event',
            'id',
            'component = :component AND eventtype = :eventtype AND instance = :instance',
            ['component' => self::COMPONENT, 'eventtype' => self::EVENTTYPE, 'instance' => $bookingid]
        );
        foreach ($ids as $id) {
            calendar_event::load($id)->delete();
        }
    }

    /**
     * Deletes the events of the bookings that match a condition on the bookings table.
     *
     * Direct deletion, for bulk removals (a space or a person going away) where the bookings
     * go too and nothing listens for single event deletions.
     *
     * @param  string $where  SQL condition on the bookings table.
     * @param  array  $params Its parameters, named.
     * @return void
     */
    public static function delete_events_where(string $where, array $params): void {
        global $DB;

        $DB->delete_records_select(
            'event',
            'component = :tbcomponent AND eventtype = :tbeventtype
                AND instance IN (SELECT id FROM {' . booking::TABLE . '} WHERE ' . $where . ')',
            $params + ['tbcomponent' => self::COMPONENT, 'tbeventtype' => self::EVENTTYPE]
        );
    }
}
