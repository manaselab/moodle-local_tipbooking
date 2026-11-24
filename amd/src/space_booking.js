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
 * Booking on the page of a space: changing day, choosing a range of slots, booking and
 * cancelling.
 *
 * The server decides: every check made here only spares a useless request, and after any
 * booking or cancellation the day is read again.
 *
 * @module     local_tipbooking/space_booking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';
import {add as addToast} from 'core/toast';
import {getString, getStrings} from 'core/str';
import {confirmAndCancel} from 'local_tipbooking/cancel_booking';

const COMPONENT = 'local_tipbooking';

/** @var {Number} Days looked ahead for the next day with free slots. */
const DAYS_AHEAD = 30;

const SELECTORS = {
    grid: '[data-region="local_tipbooking-grid"]',
    day: '[data-action="local_tipbooking-day"]',
    daySub: '[data-region="local_tipbooking-day-sub"]',
    slot: '[data-action="local_tipbooking-slot"]',
    indexed: '[data-index]',
    cancel: '[data-action="local_tipbooking-cancel"]',
    nextFree: '[data-action="local_tipbooking-nextfree"]',
    rangeBar: '[data-region="local_tipbooking-rangebar"]',
    rangeLabel: '[data-region="local_tipbooking-rangelabel"]',
    clear: '[data-action="local_tipbooking-clear"]',
    book: '[data-action="local_tipbooking-book"]',
};

/**
 * Builds the formatter of times of a time zone.
 *
 * @param {String} timezone The time zone.
 * @returns {Intl.DateTimeFormat} The formatter.
 */
const timeFormatter = timezone => new Intl.DateTimeFormat(document.documentElement.lang || undefined, {
    timeZone: timezone,
    hour: '2-digit',
    minute: '2-digit',
    hourCycle: 'h23',
});

/**
 * A range of time as HH:MM–HH:MM.
 *
 * @param {Intl.DateTimeFormat} formatter Formatter of the time zone.
 * @param {Number} start Start, as a Unix timestamp.
 * @param {Number} end End, as a Unix timestamp.
 * @returns {String} The range.
 */
const rangeLabel = (formatter, start, end) => formatter.format(start * 1000) + '–' + formatter.format(end * 1000);

/**
 * Moves a day some days forward.
 *
 * @param {String} date The day, as YYYY-MM-DD.
 * @param {Number} days Days to add.
 * @returns {String} The new day, as YYYY-MM-DD.
 */
const addDays = (date, days) => {
    const [year, month, day] = date.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day + days)).toISOString().slice(0, 10);
};

/**
 * Controller of one page of a space.
 */
class SpaceBooking {
    /**
     * Prepares the page.
     *
     * @param {HTMLElement} root The page.
     * @param {Object} strings The strings used to write ranges and days.
     */
    constructor(root, strings) {
        this.root = root;
        this.config = JSON.parse(root.dataset.config);
        this.strings = strings;
        this.spaceTime = timeFormatter(this.config.timezone);
        this.userTime = timeFormatter(this.config.usertimezone);
        this.date = this.config.date;
        this.slots = [];
        this.first = null;
        this.last = null;
        this.readSlots();
        root.addEventListener('click', e => this.handleClick(e));
    }

    /**
     * Sends each click to its action.
     *
     * @param {Event} e The click.
     */
    handleClick(e) {
        const day = e.target.closest(SELECTORS.day);
        if (day) {
            this.chooseDay(day);
            return;
        }
        const slot = e.target.closest(SELECTORS.slot);
        if (slot) {
            this.chooseSlot(Number(slot.dataset.index));
            return;
        }
        const cancel = e.target.closest(SELECTORS.cancel);
        if (cancel) {
            this.cancelBooking(cancel);
            return;
        }
        if (e.target.closest(SELECTORS.clear)) {
            this.clearRange();
        } else if (e.target.closest(SELECTORS.book)) {
            this.confirmBooking(e.target.closest(SELECTORS.book));
        } else if (e.target.closest(SELECTORS.nextFree)) {
            this.goToNextFreeDay(e.target.closest(SELECTORS.nextFree));
        }
    }

    /**
     * Reads the slots of the day drawn in the grid.
     */
    readSlots() {
        this.slots = [];
        this.root.querySelectorAll(`${SELECTORS.grid} ${SELECTORS.indexed}`).forEach(node => {
            this.slots[Number(node.dataset.index)] = {
                node,
                free: node.matches(SELECTORS.slot),
                start: Number(node.dataset.start),
                end: Number(node.dataset.end),
            };
        });
    }

    /**
     * Shows another day of the week.
     *
     * @param {HTMLElement} button The button of the day.
     */
    chooseDay(button) {
        this.root.querySelectorAll(SELECTORS.day).forEach(node => {
            const selected = node === button;
            node.classList.toggle('is-selected', selected);
            node.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
        this.date = button.dataset.date;
        const url = new URL(this.config.pageurl);
        url.searchParams.set('date', this.date);
        window.history.replaceState(null, '', url.toString());
        this.loadDay();
    }

    /**
     * Reads the chosen day from the server and draws it.
     *
     * @returns {Promise} Resolved once the day is drawn.
     */
    async loadDay() {
        this.clearRange();
        const grid = this.root.querySelector(SELECTORS.grid);
        const count = Math.max(this.slots.length, 4);
        grid.innerHTML = '<div class="local_tipbooking-slots" aria-busy="true">' +
            '<span class="local_tipbooking-slot is-skeleton"></span>'.repeat(count) + '</div>';

        try {
            const day = await Ajax.call([{
                methodname: 'local_tipbooking_get_day_slots',
                args: {spaceid: this.config.spaceid, date: this.date},
            }])[0];
            const {html, js} = await Templates.renderForPromise('local_tipbooking/slot_grid', this.gridContext(day));
            Templates.replaceNodeContents(grid, html, js);
            this.readSlots();
            this.updateDayButton(day);
        } catch (error) {
            Notification.exception(error);
        }
    }

    /**
     * Context of local_tipbooking/slot_grid; it mirrors space_page::grid_context() in PHP.
     *
     * @param {Object} day Answer of local_tipbooking_get_day_slots.
     * @returns {Object} The context.
     */
    gridContext(day) {
        const button = this.dayButton();
        const mybooking = day.mybooking ? {
            id: day.mybooking.id,
            label: rangeLabel(this.spaceTime, day.mybooking.timestart, day.mybooking.timeend),
        } : null;
        const slots = day.slots.map((slot, index) => {
            const free = slot.status === 'free';
            return {
                index,
                start: slot.start,
                end: slot.end,
                label: rangeLabel(this.spaceTime, slot.start, slot.end),
                free: free && this.config.canbook,
                busy: slot.status === 'busy',
                mine: slot.status === 'mine',
                past: slot.status === 'past',
                blocked: slot.status === 'blocked' || (free && !this.config.canbook),
            };
        });

        return {
            date: day.date,
            daylabel: button ? button.dataset.daylabel : day.date,
            hasslots: slots.length > 0,
            closed: slots.length === 0,
            full: slots.length > 0 && day.freecount === 0 && !mybooking,
            hasmybooking: !!mybooking,
            mybooking,
            slots,
        };
    }

    /**
     * The button of the chosen day in the week, if it is there.
     *
     * @returns {HTMLElement|null} The button.
     */
    dayButton() {
        return this.root.querySelector(`${SELECTORS.day}[data-date="${this.date}"]`);
    }

    /**
     * Writes the new state of the chosen day under its button.
     *
     * @param {Object} day Answer of local_tipbooking_get_day_slots.
     */
    updateDayButton(day) {
        const button = this.dayButton();
        if (!button || button.disabled) {
            return;
        }
        const mine = !!day.mybooking;
        const full = !mine && day.freecount === 0;
        let sub = this.strings.free.replace('{$a}', day.freecount);
        if (mine) {
            sub = this.strings.mine;
        } else if (full) {
            sub = this.strings.full;
        }
        button.classList.toggle('is-mine', mine);
        button.classList.toggle('is-full', full);
        button.querySelector(SELECTORS.daySub).textContent = sub;
        button.setAttribute('aria-label', `${button.dataset.daylabel}: ${sub}`);
    }

    /**
     * Adds a free slot to the range, or starts the range with it.
     *
     * The range is always of consecutive free slots; a click that would leave a gap or a
     * taken slot inside it starts again.
     *
     * @param {Number} index Position of the slot in the day.
     */
    chooseSlot(index) {
        if (this.first === null) {
            this.setRange(index, index);
            return;
        }
        if (index >= this.first && index <= this.last) {
            this.clearRange();
            return;
        }

        const first = Math.min(this.first, index);
        const last = Math.max(this.last, index);
        for (let i = first; i <= last; i++) {
            if (!this.slots[i] || !this.slots[i].free) {
                this.clearRange();
                addToast(getString('range:notcontiguous', COMPONENT), {type: 'warning'});
                return;
            }
        }
        this.setRange(first, last);
    }

    /**
     * Marks a range of slots as chosen and shows the bar.
     *
     * @param {Number} first Position of the first slot.
     * @param {Number} last Position of the last slot.
     */
    setRange(first, last) {
        this.first = first;
        this.last = last;
        this.slots.forEach((slot, index) => {
            if (slot.free) {
                const chosen = index >= first && index <= last;
                slot.node.classList.toggle('is-range', chosen);
                slot.node.setAttribute('aria-pressed', chosen ? 'true' : 'false');
            }
        });

        const bar = this.root.querySelector(SELECTORS.rangeBar);
        const {start, end} = this.range();
        bar.querySelector(SELECTORS.rangeLabel).textContent =
            `${rangeLabel(this.spaceTime, start, end)} · ${this.duration(start, end)}`;
        bar.hidden = false;
    }

    /**
     * Forgets the chosen range.
     */
    clearRange() {
        this.first = null;
        this.last = null;
        this.slots.forEach(slot => {
            if (slot.free) {
                slot.node.classList.remove('is-range');
                slot.node.setAttribute('aria-pressed', 'false');
            }
        });
        this.root.querySelector(SELECTORS.rangeBar).hidden = true;
    }

    /**
     * Start and end of the chosen range.
     *
     * @returns {Object} start and end, as Unix timestamps.
     */
    range() {
        return {start: this.slots[this.first].start, end: this.slots[this.last].end};
    }

    /**
     * Length of a range, as "3 h" or "1 h 30 min".
     *
     * @param {Number} start Start, as a Unix timestamp.
     * @param {Number} end End, as a Unix timestamp.
     * @returns {String} The length.
     */
    duration(start, end) {
        const minutes = Math.round((end - start) / 60);
        const parts = [];
        if (minutes >= 60) {
            parts.push(this.strings.hours.replace('{$a}', Math.floor(minutes / 60)));
        }
        if (minutes % 60) {
            parts.push(this.strings.minutes.replace('{$a}', minutes % 60));
        }
        return parts.join(' ');
    }

    /**
     * Asks for confirmation and books the chosen range.
     *
     * @param {HTMLElement} trigger The button that asked for it.
     */
    async confirmBooking(trigger) {
        if (this.first === null) {
            return;
        }
        const {start, end} = this.range();
        const label = rangeLabel(this.spaceTime, start, end);
        const local = rangeLabel(this.userTime, start, end);
        const button = this.dayButton();

        try {
            const {html} = await Templates.renderForPromise('local_tipbooking/booking_confirm', {
                spacename: this.config.spacename,
                daylabel: button ? button.dataset.daylabel : this.date,
                rangelabel: label,
                duration: this.duration(start, end),
                timezone: this.config.timezone,
                showlocal: local !== label,
                locallabel: local,
                usertimezone: this.config.usertimezone,
            });
            await Notification.saveCancelPromise(
                getString('confirm:title', COMPONENT),
                html,
                getString('confirm:book', COMPONENT),
                {triggerElement: trigger}
            );
        } catch (error) {
            // Cancelling the modal rejects the promise: nothing to do.
            return;
        }

        try {
            const result = await Ajax.call([{
                methodname: 'local_tipbooking_book_range',
                args: {spaceid: this.config.spaceid, timestart: start, timeend: end},
            }])[0];
            addToast(result.message, {type: result.status === 'booked' ? 'success' : 'warning'});
        } catch (error) {
            Notification.exception(error);
        }
        await this.loadDay();
    }

    /**
     * Asks for confirmation and cancels the booking of the reader that day.
     *
     * @param {HTMLElement} trigger The button with the booking.
     */
    async cancelBooking(trigger) {
        const button = this.dayButton();
        if (await confirmAndCancel(trigger, button ? button.dataset.daylabel : this.date)) {
            await this.loadDay();
        }
    }

    /**
     * Opens the next day with free slots, looking up to DAYS_AHEAD days ahead.
     *
     * The days are asked in one request.
     *
     * @param {HTMLElement} trigger The button that asked for it.
     */
    async goToNextFreeDay(trigger) {
        trigger.disabled = true;
        const dates = Array.from({length: DAYS_AHEAD}, (_, i) => addDays(this.date, i + 1));
        const requests = Ajax.call(dates.map(date => ({
            methodname: 'local_tipbooking_get_day_slots',
            args: {spaceid: this.config.spaceid, date},
        })));
        const answers = await Promise.allSettled(requests);
        const found = answers.findIndex(answer => answer.status === 'fulfilled' && answer.value.freecount > 0);

        if (found === -1) {
            trigger.disabled = false;
            addToast(getString('grid:nonextfree', COMPONENT, DAYS_AHEAD), {type: 'info'});
            return;
        }
        const url = new URL(this.config.pageurl);
        url.searchParams.set('date', dates[found]);
        window.location.assign(url.toString());
    }
}

/**
 * Starts the page of a space.
 *
 * @param {String} selector Selector of the page.
 */
export const init = async selector => {
    const root = document.querySelector(selector);
    if (!root || root.dataset.initialised) {
        return;
    }
    root.dataset.initialised = 'true';

    const [free, mine, full, hours, minutes] = await getStrings([
        {key: 'day:free', component: COMPONENT, param: '{$a}'},
        {key: 'day:mine', component: COMPONENT},
        {key: 'day:full', component: COMPONENT},
        {key: 'duration:hours', component: COMPONENT, param: '{$a}'},
        {key: 'duration:minutes', component: COMPONENT, param: '{$a}'},
    ]);
    new SpaceBooking(root, {free, mine, full, hours, minutes});
};
