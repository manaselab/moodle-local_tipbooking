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
 * Creates or edits a space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use core\url;
use local_tipbooking\booking_manager;
use local_tipbooking\form\space_form;
use local_tipbooking\form\space_form_data;
use local_tipbooking\models\space;
use local_tipbooking\space_manager;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = optional_param('id', 0, PARAM_INT);

$pageurl = new url('/local/tipbooking/edit.php', ['id' => $id]);
admin_externalpage_setup('local_tipbooking_manage', '', null, $pageurl);
$context = context_system::instance();
$manageurl = new url('/local/tipbooking/manage.php');

$space = $id ? space_manager::get($id) : new space();
$managers = space_manager::get_manager_options();
$adminids = $id ? space_manager::get_admin_ids($id) : (isset($managers[$USER->id]) ? [(int) $USER->id] : []);
$futurebookings = $id ? count(booking_manager::get_upcoming_of_space($id)) : 0;

$form = new space_form($pageurl, ['managers' => $managers, 'futurebookings' => $futurebookings]);

$data = space_form_data::to_form($space, $adminids);
$data = file_prepare_standard_editor($data, 'description', space_form::editor_options(), $context,
    'local_tipbooking', 'description', $id ?: null);
$data = file_prepare_standard_filemanager($data, 'image', space_form::image_options(), $context,
    'local_tipbooking', space_manager::FILEAREA_IMAGE, $id ?: null);
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect($manageurl);
}

if ($formdata = $form->get_data()) {
    $formdata = file_postupdate_standard_editor($formdata, 'description', space_form::editor_options(), $context,
        'local_tipbooking', 'description', $id ?: 0);
    $space = space_form_data::to_space($formdata, $space);
    $admins = array_map('intval', (array) $formdata->admins);
    $space = $id ? space_manager::update($space, $admins) : space_manager::create($space, $admins);
    file_postupdate_standard_filemanager($formdata, 'image', space_form::image_options(), $context,
        'local_tipbooking', space_manager::FILEAREA_IMAGE, $space->id);

    redirect($manageurl, get_string('spacesaved', 'local_tipbooking'), null, notification::NOTIFY_SUCCESS);
}

$title = get_string($id ? 'editspace' : 'newspace', 'local_tipbooking');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->navbar->add($title);

echo $OUTPUT->header();
echo $OUTPUT->heading($title);
$form->display();
echo $OUTPUT->render_from_template('local_tipbooking/slot_preview', []);
echo $OUTPUT->footer();
