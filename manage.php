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
 * Lists the spaces and hides, shows or deletes them.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\notification;
use core\output\single_button;
use core\url;
use local_tipbooking\booking_manager;
use local_tipbooking\space_manager;
use local_tipbooking\table\spaces_table;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$pageurl = new url('/local/tipbooking/manage.php');
admin_externalpage_setup('local_tipbooking_manage', '', null, $pageurl);
$context = context_system::instance();

if ($id && in_array($action, ['hide', 'show'], true)) {
    require_sesskey();
    space_manager::set_visible($id, $action === 'show');
    redirect($pageurl);
}

if ($id && $action === 'delete') {
    $space = space_manager::get($id);
    $name = format_string($space->name, true, ['context' => $context]);

    if (optional_param('confirm', 0, PARAM_BOOL)) {
        require_sesskey();
        space_manager::delete($id);
        redirect($pageurl, get_string('spacedeleted', 'local_tipbooking', $name), null, notification::NOTIFY_SUCCESS);
    }

    $future = count(booking_manager::get_upcoming_of_space($id));
    $message = get_string('confirmdeletespace', 'local_tipbooking', $name);
    if ($future > 0) {
        $message .= ' ' . get_string('confirmdeletespacebookings', 'local_tipbooking', $future);
    }

    echo $OUTPUT->header();
    echo $OUTPUT->confirm(
        $message,
        new url($pageurl, ['action' => 'delete', 'id' => $id, 'confirm' => 1, 'sesskey' => sesskey()]),
        $pageurl
    );
    echo $OUTPUT->footer();
    die();
}

$table = new spaces_table($pageurl, $download);
if ($table->is_downloading()) {
    $table->out(0, false);
    die();
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('managespaces', 'local_tipbooking'));
echo $OUTPUT->render(new single_button(
    new url('/local/tipbooking/edit.php'),
    get_string('newspace', 'local_tipbooking'),
    'get',
    single_button::BUTTON_PRIMARY
));
$table->out(20, true);
echo $OUTPUT->footer();
