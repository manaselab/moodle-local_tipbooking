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
 * Notifications of bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking;

use context_system;
use core\message\message;
use core\url;
use core_user;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use stdClass;

/**
 * Tells the person who booked and the managers of the space about each booking and
 * cancellation.
 *
 * Each message is written in the language of whoever receives it, and the times are given
 * in the time zone of the space, which is where the booking happens.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifier {
    /**
     * Sends the messages of a new booking.
     *
     * @param  booking $booking The booking.
     * @return void
     */
    public static function booking_created(booking $booking): void {
        $space = space_manager::get($booking->spaceid);
        $holder = core_user::get_user($booking->userid, '*', MUST_EXIST);

        self::send($holder, 'bookingconfirmed', $space, $booking, $holder, null);
        foreach (self::admins_to_notify($space, $booking) as $admin) {
            self::send($admin, 'adminbookingcreated', $space, $booking, $holder, null);
        }
    }

    /**
     * Sends the messages of a cancelled booking.
     *
     * @param  booking $booking The booking, already cancelled.
     * @return void
     */
    public static function booking_cancelled(booking $booking): void {
        $space = space_manager::get($booking->spaceid);
        $holder = core_user::get_user($booking->userid, '*', MUST_EXIST);
        $canceller = $booking->cancelledby ? core_user::get_user($booking->cancelledby) : null;

        self::send($holder, 'bookingcancelled', $space, $booking, $holder, $canceller ?: null);
        foreach (self::admins_to_notify($space, $booking) as $admin) {
            self::send($admin, 'adminbookingcancelled', $space, $booking, $holder, $canceller ?: null);
        }
    }

    /**
     * The managers of a space who get the messages of a booking.
     *
     * A manager who is also the holder gets the message of the holder only.
     *
     * @param  space      $space   The space.
     * @param  booking    $booking The booking.
     * @return stdClass[]          The users.
     */
    private static function admins_to_notify(space $space, booking $booking): array {
        $admins = [];
        foreach (space_manager::get_admin_ids($space->id) as $adminid) {
            if ($adminid === $booking->userid) {
                continue;
            }
            $admin = core_user::get_user($adminid);
            if ($admin && !$admin->deleted && !$admin->suspended) {
                $admins[] = $admin;
            }
        }
        return $admins;
    }

    /**
     * Writes and sends one message in the language of its recipient.
     *
     * @param  stdClass      $to        Recipient.
     * @param  string        $name      Name of the message provider; also the prefix of its strings.
     * @param  space         $space     The space.
     * @param  booking       $booking   The booking.
     * @param  stdClass      $holder    Who holds the booking.
     * @param  stdClass|null $canceller Who cancelled it, for cancellations.
     * @return void
     */
    private static function send(
        stdClass $to,
        string $name,
        space $space,
        booking $booking,
        stdClass $holder,
        ?stdClass $canceller
    ): void {
        $previous = force_current_language($to->lang ?? '');
        try {
            $context = context_system::instance();
            $timezone = $space->get_timezone()->getName();
            $a = (object) [
                'space' => format_string($space->name, true, ['context' => $context]),
                'day' => userdate($booking->timestart, get_string('strftimedaydate', 'langconfig'), $timezone),
                'from' => userdate($booking->timestart, get_string('strftimetime', 'langconfig'), $timezone),
                'to' => userdate($booking->timeend, get_string('strftimetime', 'langconfig'), $timezone),
                'timezone' => $timezone,
                'user' => fullname($holder),
                'canceller' => $canceller ? fullname($canceller) : '',
            ];
            $url = new url('/local/tipbooking/space.php', ['id' => $space->id]);

            $message = new message();
            $message->component = 'local_tipbooking';
            $message->name = $name;
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $to;
            $message->subject = get_string('message:' . $name . ':subject', 'local_tipbooking', $a);
            $message->fullmessage = get_string('message:' . $name . ':body', 'local_tipbooking', $a);
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = text_to_html($message->fullmessage, false, false, true);
            $message->smallmessage = $message->subject;
            $message->notification = 1;
            $message->contexturl = $url->out(false);
            $message->contexturlname = $a->space;
        } finally {
            force_current_language($previous);
        }

        message_send($message);
    }
}
