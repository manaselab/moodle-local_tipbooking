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
 * Showcase of the bookable spaces.
 *
 * The filters travel in the address as ?q=text&day=YYYY-MM-DD, so a filtered list can be
 * shared and paged; the form only writes that address.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\paging_bar;
use core\url;
use local_tipbooking\booking_manager;
use local_tipbooking\form\catalogue_filter_form;
use local_tipbooking\output\catalogue;
use local_tipbooking\space_manager;

require_once(__DIR__ . '/../../config.php');

/** @var int Spaces per page. */
const LOCAL_TIPBOOKING_PERPAGE = 12;

require_login(null, false);
if (isguestuser()) {
    redirect(get_login_url());
}
$context = context_system::instance();
require_capability('local/tipbooking:view', $context);

$text = trim(optional_param('q', '', PARAM_TEXT));
$date = optional_param('day', '', PARAM_ALPHANUMEXT);
$page = optional_param('page', 0, PARAM_INT);
if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $date = '';
}

$baseurl = new url('/local/tipbooking/index.php');
$filterurl = new url($baseurl, array_filter(['q' => $text, 'day' => $date]));

$PAGE->set_context($context);
$PAGE->set_url(new url($filterurl, $page ? ['page' => $page] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('catalogue:title', 'local_tipbooking'));
$PAGE->set_heading(get_string('catalogue:title', 'local_tipbooking'));

$form = new catalogue_filter_form($baseurl, ['clearurl' => $baseurl], 'get', '', ['class' => 'local_tipbooking-filterform']);
if ($data = $form->get_data()) {
    redirect(new url($baseurl, array_filter([
        'q' => trim((string) ($data->q ?? '')),
        'day' => !empty($data->date) ? userdate($data->date, '%Y-%m-%d', 99, false) : '',
    ])));
}
$form->set_data([
    'q' => $text,
    'date' => $date !== '' ? make_timestamp(...array_map('intval', explode('-', $date))) : 0,
]);

$result = space_manager::search_visible($text, $date !== '' ? $date : null, LOCAL_TIPBOOKING_PERPAGE,
    $page * LOCAL_TIPBOOKING_PERPAGE);

$imageurls = [];
foreach ($result['spaces'] as $space) {
    $imageurls[$space->id] = space_manager::get_image_url($space->id);
}

$datelabel = null;
if ($date !== '') {
    $datelabel = userdate(
        make_timestamp(...array_map('intval', explode('-', $date))),
        get_string('strftimedaydate', 'langconfig')
    );
}

$catalogue = new catalogue(
    $result['spaces'],
    $result['total'],
    $imageurls,
    booking_manager::count_upcoming_by_space((int) $USER->id),
    booking_manager::count_user_bookings((int) $USER->id, true),
    has_capability('local/tipbooking:manage', $context),
    $text !== '' || $date !== '',
    $datelabel,
    $form->render(),
    $OUTPUT->render(new paging_bar($result['total'], $page, LOCAL_TIPBOOKING_PERPAGE, $filterurl))
);

echo $OUTPUT->header();
echo $PAGE->get_renderer('local_tipbooking')->render($catalogue);
echo $OUTPUT->footer();
