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

namespace local_tipbooking\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use core\output\html_writer;
use core\url;
use moodleform;

/**
 * Filters of the bookings of a space: days, state and person, sent by GET.
 *
 * The space travels in the address of the form (moodleform keeps its parameters as hidden
 * fields). The days are named fromdate and todate because from and to are the parameters
 * of the page, which carry the days as text.
 *
 * Custom data: 'clearurl' (url) — the bookings of the space without filters.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_bookings_filter_form extends moodleform {
    /** @var string Every booking. */
    public const STATUS_ALL = 'all';

    /** @var string Active bookings only. */
    public const STATUS_ACTIVE = 'active';

    /** @var string Cancelled bookings only. */
    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Builds the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;

        $mform->addElement('date_selector', 'fromdate', get_string('filter:from', 'local_tipbooking'), ['optional' => true]);
        $mform->addElement('date_selector', 'todate', get_string('filter:to', 'local_tipbooking'), ['optional' => true]);

        $mform->addElement('select', 'status', get_string('table:status', 'local_tipbooking'), [
            self::STATUS_ALL => get_string('filter:statusall', 'local_tipbooking'),
            self::STATUS_ACTIVE => get_string('filter:statusactive', 'local_tipbooking'),
            self::STATUS_CANCELLED => get_string('filter:statuscancelled', 'local_tipbooking'),
        ]);
        $mform->setDefault('status', self::STATUS_ALL);

        $mform->addElement('text', 'user', get_string('table:user', 'local_tipbooking'),
            ['placeholder' => get_string('filter:userplaceholder', 'local_tipbooking'), 'size' => 30]);
        $mform->setType('user', PARAM_TEXT);

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

    /**
     * The last day cannot be before the first one.
     *
     * @param  array $data  Submitted data.
     * @param  array $files Submitted files.
     * @return array        Errors by element.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        if (!empty($data['fromdate']) && !empty($data['todate']) && $data['todate'] < $data['fromdate']) {
            $errors['todate'] = get_string('error:datetobeforefrom', 'local_tipbooking');
        }
        return $errors;
    }
}
