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
 * Page of one space: its details, its week and the slots of a day to book a range.
 *
 * ?date=YYYY-MM-DD opens that day (in the time zone of the space); without it, the page
 * opens today or the first bookable day.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use local_tipbooking\output\space_page;
use local_tipbooking\output\space_summary;
use local_tipbooking\space_manager;

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$date = optional_param('date', '', PARAM_ALPHANUMEXT);

require_login(null, false);
if (isguestuser()) {
    redirect(get_login_url());
}
$context = context_system::instance();
require_capability('local/tipbooking:view', $context);

try {
    $space = space_manager::get($id);
} catch (dml_missing_record_exception $e) {
    $space = null;
}
$canmanage = $space !== null && space_manager::can_manage_space($space->id, (int) $USER->id);
if ($space === null || (!$space->visible && !$canmanage)) {
    throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
}

$pageurl = new url('/local/tipbooking/space.php', ['id' => $space->id]);
$name = space_summary::name($space);

$PAGE->set_context($context);
$PAGE->set_url($date !== '' ? new url($pageurl, ['date' => $date]) : $pageurl);
$PAGE->set_pagelayout('standard');
$PAGE->set_title($name);
$PAGE->set_heading(get_string('catalogue:title', 'local_tipbooking'));
$PAGE->navbar->add(get_string('catalogue:title', 'local_tipbooking'), new url('/local/tipbooking/index.php'));
$PAGE->navbar->add($name, $pageurl);

$page = new space_page(
    $space,
    $date,
    (int) $USER->id,
    $canmanage,
    has_capability('local/tipbooking:book', $context),
    space_manager::get_image_url($space->id)
);

echo $OUTPUT->header();
echo $PAGE->get_renderer('local_tipbooking')->render($page);
echo $OUTPUT->footer();
