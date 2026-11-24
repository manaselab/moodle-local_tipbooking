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
 * Cancelling a booking after a confirmation, shared by the page of a space, "My bookings"
 * and the bookings of a space.
 *
 * @module     local_tipbooking/cancel_booking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import {add as addToast} from 'core/toast';
import {getString} from 'core/str';

const COMPONENT = 'local_tipbooking';

/**
 * Asks for confirmation and cancels a booking.
 *
 * @param {HTMLElement} trigger Button with data-bookingid and data-label (the range), and
 *        data-user when a manager cancels the booking of somebody else; it gets the focus
 *        back when the modal closes.
 * @param {String} day The day of the booking, ready to print.
 * @returns {Promise<Boolean>} True when the booking was cancelled.
 */
export const confirmAndCancel = async(trigger, day) => {
    try {
        await Notification.deleteCancelPromise(
            getString('cancel:title', COMPONENT),
            trigger.dataset.user
                ? getString('spacebookings:cancelquestion', COMPONENT, {
                    user: trigger.dataset.user,
                    range: trigger.dataset.label,
                    day,
                })
                : getString('cancel:question', COMPONENT, {range: trigger.dataset.label, day}),
            getString('cancel:confirm', COMPONENT),
            {triggerElement: trigger}
        );
    } catch (error) {
        // Closing the modal rejects the promise: nothing to cancel.
        return false;
    }

    try {
        const result = await Ajax.call([{
            methodname: 'local_tipbooking_cancel_booking',
            args: {bookingid: Number(trigger.dataset.bookingid)},
        }])[0];
        addToast(result.message, {type: 'success'});
        return true;
    } catch (error) {
        Notification.exception(error);
        return false;
    }
};

/**
 * Lets the "Cancel" buttons of a list of bookings work.
 *
 * Each button carries data-bookingid, data-label and data-daylabel, inside an item
 * (data-region="local_tipbooking-booking") or a table row. After cancelling, the item stays
 * with the "Cancelled" badge (data-region="local_tipbooking-status") and without the button,
 * so the reader sees what changed.
 *
 * @param {String} selector Selector of the list.
 */
export const initList = selector => {
    const root = document.querySelector(selector);
    if (!root || root.dataset.initialised) {
        return;
    }
    root.dataset.initialised = 'true';

    root.addEventListener('click', async e => {
        const trigger = e.target.closest('[data-action="local_tipbooking-cancel"]');
        if (!trigger || !await confirmAndCancel(trigger, trigger.dataset.daylabel)) {
            return;
        }
        const item = trigger.closest('[data-region="local_tipbooking-booking"], tr');
        const badge = item.querySelector('[data-region="local_tipbooking-status"]');
        badge.textContent = await getString('mybookings:statuscancelled', COMPONENT);
        badge.className = 'badge bg-secondary text-dark';
        // The button disappears: the focus moves to the new state instead of being lost.
        badge.setAttribute('tabindex', '-1');
        trigger.remove();
        badge.focus();
    });
};
