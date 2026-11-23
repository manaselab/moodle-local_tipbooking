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
 * Live preview of the slots in the form of a space.
 *
 * It repeats the calculation of the server only to tell whoever edits what the opening hours
 * and the slot length give; the server works the slots out again when people book.
 *
 * @module     local_tipbooking/slot_preview
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';

const SELECTORS = {
    region: '[data-region="local_tipbooking-slot-preview"]',
    hourfrom: 'select[name="hourfrom"]',
    hourto: 'select[name="hourto"]',
    duration: 'select[name="slotduration"]',
};

/**
 * Minutes from midnight as HH:MM.
 *
 * @param {Number} minutes Minutes from midnight.
 * @returns {String} The time.
 */
const clock = (minutes) => {
    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(rest).padStart(2, '0')}`;
};

/**
 * Writes the preview of the current values.
 *
 * @param {HTMLElement} region Where the preview goes.
 * @param {HTMLFormElement} form The form of the space.
 * @returns {Promise<void>}
 */
const render = async(region, form) => {
    const from = parseInt(form.querySelector(SELECTORS.hourfrom).value, 10);
    const to = parseInt(form.querySelector(SELECTORS.hourto).value, 10);
    const duration = parseInt(form.querySelector(SELECTORS.duration).value, 10);

    const slots = [];
    let start = from;
    if (duration > 0) {
        for (; start + duration <= to; start += duration) {
            slots.push(`${clock(start)}–${clock(start + duration)}`);
        }
    }

    if (!slots.length) {
        region.textContent = await getString('form:slotpreviewnone', 'local_tipbooking');
        region.classList.add('text-danger');
        return;
    }

    region.classList.remove('text-danger');
    let text = await getString('form:slotpreviewlist', 'local_tipbooking', slots.join(', '));
    if (start < to) {
        text += ' ' + await getString('form:slotpreviewleftover', 'local_tipbooking', {from: clock(start), to: clock(to)});
    }
    region.textContent = text;
};

/**
 * Starts the preview.
 */
export const init = () => {
    const region = document.querySelector(SELECTORS.region);
    if (!region) {
        return;
    }
    const form = region.closest('form');
    if (!form) {
        return;
    }

    [SELECTORS.hourfrom, SELECTORS.hourto, SELECTORS.duration].forEach((selector) => {
        const element = form.querySelector(selector);
        if (element) {
            element.addEventListener('change', () => render(region, form));
        }
    });
    render(region, form);
};
