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
 * Filters of the showcase of spaces.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use core\output\html_writer;
use core\url;
use moodleform;

/**
 * Text and "free on" filters of the showcase, sent by GET so a filtered list can be shared.
 *
 * Custom data: 'clearurl' (url) — address of the showcase without filters.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue_filter_form extends moodleform {
    /**
     * Builds the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('text', 'q', get_string('filter:search', 'local_tipbooking'),
            ['placeholder' => get_string('filter:searchplaceholder', 'local_tipbooking'), 'size' => 30]);
        $mform->setType('q', PARAM_TEXT);

        $mform->addElement('date_selector', 'date', get_string('filter:date', 'local_tipbooking'), ['optional' => true]);

        $buttons = [];
        $buttons[] = $mform->createElement('submit', 'filter', get_string('filter:apply', 'local_tipbooking'));
        $clearurl = $this->_customdata['clearurl'] ?? null;
        if ($clearurl instanceof url) {
            $buttons[] = $mform->createElement('static', 'clear', '',
                html_writer::link($clearurl, get_string('filter:clear', 'local_tipbooking'),
                    ['class' => 'btn btn-link']));
        }
        $mform->addGroup($buttons, 'buttons', '', ' ', false);

        $mform->disable_form_change_checker();
    }
}
