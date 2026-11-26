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
 * Form to create and edit a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use core\context\system;
use core_date;
use moodleform;

/**
 * Form of a space: general data, availability and managers.
 *
 * Custom data:
 * - 'managers' (array): user id => name of the people who can manage spaces.
 * - 'futurebookings' (int): bookings still to come, shown as a notice when editing.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_form extends moodleform {
    /** @var int Step of the opening and closing times, in minutes. */
    public const TIME_STEP = 15;

    /** @var int[] Slot lengths offered, in hours. */
    public const DURATIONS = [1, 2, 3, 4, 5, 6, 8];

    /**
     * Options of the file manager of the image.
     *
     * @return array The options.
     */
    public static function image_options(): array {
        return [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.jpg', '.jpeg', '.png', '.webp'],
        ];
    }

    /**
     * Options of the editor of the description.
     *
     * The context is the one the description is saved in: without it the editor ignores the
     * filters of that context and core warns about it.
     *
     * @return array The options.
     */
    public static function editor_options(): array {
        return ['maxfiles' => 0, 'noclean' => false, 'trusttext' => false, 'context' => system::instance()];
    }

    /**
     * Builds the form.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $managers = $this->_customdata['managers'] ?? [];
        $futurebookings = (int) ($this->_customdata['futurebookings'] ?? 0);

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        if ($futurebookings > 0) {
            $mform->addElement('static', 'futurenotice', '',
                get_string('form:futurebookingskept', 'local_tipbooking', $futurebookings));
        }

        // General.
        $mform->addElement('header', 'general', get_string('form:general', 'local_tipbooking'));

        $mform->addElement('text', 'name', get_string('form:name', 'local_tipbooking'), ['size' => 60, 'maxlength' => 255]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'form:name', 'local_tipbooking');

        $mform->addElement('editor', 'description_editor', get_string('form:description', 'local_tipbooking'),
            null, self::editor_options());
        $mform->setType('description_editor', PARAM_RAW);

        $mform->addElement('filemanager', 'image_filemanager', get_string('form:image', 'local_tipbooking'),
            null, self::image_options());

        $mform->addElement('advcheckbox', 'visible', get_string('form:visible', 'local_tipbooking'),
            get_string('form:visible_desc', 'local_tipbooking'));
        $mform->setDefault('visible', 1);

        // Availability.
        $mform->addElement('header', 'availability', get_string('form:availability', 'local_tipbooking'));
        $mform->setExpanded('availability');

        $mform->addElement('date_selector', 'datefrom', get_string('form:datefrom', 'local_tipbooking'));
        $mform->addElement('date_selector', 'dateto', get_string('form:dateto', 'local_tipbooking'));
        $mform->setDefault('dateto', time() + 90 * DAYSECS);

        $times = self::time_options();
        $hours = [];
        $hours[] = $mform->createElement('select', 'hourfrom', get_string('form:hourfrom', 'local_tipbooking'), $times);
        $hours[] = $mform->createElement('select', 'hourto', get_string('form:hourto', 'local_tipbooking'), $times);
        $mform->addGroup($hours, 'hours', get_string('form:hours', 'local_tipbooking'), ' – ', false);
        $mform->setDefault('hourfrom', 480);
        $mform->setDefault('hourto', 1080);

        $durations = [];
        foreach (self::DURATIONS as $hoursnumber) {
            $durations[$hoursnumber * 60] = get_string('form:durationhours', 'local_tipbooking', $hoursnumber);
        }
        $mform->addElement('select', 'slotduration', get_string('form:slotduration', 'local_tipbooking'), $durations);
        $mform->setDefault('slotduration', 60);

        $mform->addElement('static', 'slotpreview', get_string('form:slotpreview', 'local_tipbooking'),
            '<div data-region="local_tipbooking-slot-preview" aria-live="polite"></div>');

        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            $days[] = $mform->createElement('advcheckbox', 'weekday' . $day, '',
                get_string('form:weekday' . $day, 'local_tipbooking'), null, [0, 1]);
            $mform->setDefault('weekday' . $day, $day <= 5 ? 1 : 0);
        }
        $mform->addGroup($days, 'weekdays', get_string('form:weekdays', 'local_tipbooking'), ' ', false);

        $zones = core_date::get_list_of_timezones(null, true);
        $mform->addElement('select', 'timezone', get_string('form:timezone', 'local_tipbooking'), $zones);
        $mform->setDefault('timezone', core_date::get_server_timezone());
        $mform->addHelpButton('timezone', 'form:timezone', 'local_tipbooking');

        // Notifications.
        $mform->addElement('header', 'notifications', get_string('form:notifications', 'local_tipbooking'));
        $mform->setExpanded('notifications');

        $mform->addElement('autocomplete', 'admins', get_string('form:admins', 'local_tipbooking'), $managers, [
            'multiple' => true,
            'noselectionstring' => get_string('form:noadmins', 'local_tipbooking'),
        ]);
        $mform->addRule('admins', null, 'required', null, 'client');
        $mform->addHelpButton('admins', 'form:admins', 'local_tipbooking');

        $this->add_action_buttons(true, get_string('savechanges'));
    }

    /**
     * Checks the values that the rules of each element cannot.
     *
     * @param  array $data  Submitted values.
     * @param  array $files Submitted files.
     * @return array        Errors by element name.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);

        if (!empty($data['datefrom']) && !empty($data['dateto']) && $data['dateto'] < $data['datefrom']) {
            $errors['dateto'] = get_string('error:datetobeforefrom', 'local_tipbooking');
        }

        $hourfrom = (int) ($data['hourfrom'] ?? 0);
        $hourto = (int) ($data['hourto'] ?? 0);
        $duration = (int) ($data['slotduration'] ?? 0);
        if ($hourto - $hourfrom < $duration) {
            $errors['hours'] = get_string('error:noslotfits', 'local_tipbooking');
        }

        $anyday = false;
        for ($day = 1; $day <= 7; $day++) {
            $anyday = $anyday || !empty($data['weekday' . $day]);
        }
        if (!$anyday) {
            $errors['weekdays'] = get_string('error:noweekdays', 'local_tipbooking');
        }

        $managers = $this->_customdata['managers'] ?? [];
        $admins = array_filter((array) ($data['admins'] ?? []));
        if ($admins === []) {
            $errors['admins'] = get_string('error:noadmins', 'local_tipbooking');
        } else if (array_diff($admins, array_keys($managers))) {
            $errors['admins'] = get_string('error:invalidadmin', 'local_tipbooking');
        }

        return $errors;
    }

    /**
     * Times of the day in steps, as options of a select.
     *
     * @return array Minutes from midnight => "HH:MM"; 1440 is "24:00".
     */
    public static function time_options(): array {
        $options = [];
        for ($minutes = 0; $minutes <= 1440; $minutes += self::TIME_STEP) {
            $options[$minutes] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
        }
        return $options;
    }
}
