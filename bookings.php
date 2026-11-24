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
 * Bookings of one space, for its managers, with filters, download and cancellation.
 *
 * The filters travel in the address as ?spaceid=&from=YYYY-MM-DD&to=YYYY-MM-DD&status=&user=,
 * so the table pages, sorts and downloads with them; the form only writes that address.
 * The days are read in the time zone of the space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\url;
use local_tipbooking\form\space_bookings_filter_form;
use local_tipbooking\output\space_summary;
use local_tipbooking\slot_calculator;
use local_tipbooking\space_manager;
use local_tipbooking\table\space_bookings_table;

require_once(__DIR__ . '/../../config.php');

$spaceid = required_param('spaceid', PARAM_INT);
$from = optional_param('from', '', PARAM_ALPHANUMEXT);
$to = optional_param('to', '', PARAM_ALPHANUMEXT);
$status = optional_param('status', space_bookings_filter_form::STATUS_ALL, PARAM_ALPHA);
$usertext = trim(optional_param('user', '', PARAM_TEXT));
$download = optional_param('download', '', PARAM_ALPHA);

require_login(null, false);
$context = context_system::instance();
try {
    $space = space_manager::get($spaceid);
} catch (dml_missing_record_exception $e) {
    throw new moodle_exception('error:spacenotavailable', 'local_tipbooking');
}
if (isguestuser() || !space_manager::can_manage_space($space->id, (int) $USER->id)) {
    throw new required_capability_exception($context, 'local/tipbooking:manage', 'nopermissions', '');
}

$isday = fn(string $day): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $day)
    && checkdate((int) substr($day, 5, 2), (int) substr($day, 8, 2), (int) substr($day, 0, 4));
$from = $isday($from) ? $from : '';
$to = $isday($to) ? $to : '';
$statuses = [
    space_bookings_filter_form::STATUS_ALL,
    space_bookings_filter_form::STATUS_ACTIVE,
    space_bookings_filter_form::STATUS_CANCELLED,
];
$status = in_array($status, $statuses, true) ? $status : space_bookings_filter_form::STATUS_ALL;

$baseurl = new url('/local/tipbooking/bookings.php', ['spaceid' => $space->id]);
$filterurl = new url($baseurl, array_filter([
    'from' => $from,
    'to' => $to,
    'status' => $status === space_bookings_filter_form::STATUS_ALL ? '' : $status,
    'user' => $usertext,
]));
$name = space_summary::name($space);
$zone = $space->get_timezone()->getName();

$PAGE->set_context($context);
$PAGE->set_url($filterurl);
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('bookingsofnamed', 'local_tipbooking', $name));
$PAGE->set_heading(get_string('managespaces', 'local_tipbooking'));
$PAGE->navbar->add(get_string('managespaces', 'local_tipbooking'), new url('/local/tipbooking/manage.php'));
$PAGE->navbar->add(get_string('bookingsofnamed', 'local_tipbooking', $name), $baseurl);

$form = new space_bookings_filter_form(
    $baseurl,
    ['clearurl' => $baseurl],
    'get',
    '',
    ['class' => 'local_tipbooking-filterform']
);
if ($data = $form->get_data()) {
    // The selectors give midnight in the time zone of the user; the day is written as it reads.
    $day = fn($time): string => $time ? userdate($time, '%Y-%m-%d', 99, false) : '';
    redirect(new url($baseurl, array_filter([
        'from' => $day($data->fromdate ?? 0),
        'to' => $day($data->todate ?? 0),
        'status' => ($data->status ?? '') === space_bookings_filter_form::STATUS_ALL ? '' : ($data->status ?? ''),
        'user' => trim((string) ($data->user ?? '')),
    ])));
}
$totime = fn(string $day): int => $day === '' ? 0 : make_timestamp(...array_map('intval', explode('-', $day)));
$form->set_data([
    'fromdate' => $totime($from),
    'todate' => $totime($to),
    'status' => $status,
    'user' => $usertext,
]);

$table = new space_bookings_table($space, $filterurl, [
    'from' => $from === '' ? null : slot_calculator::day_key($from),
    'to' => $to === '' ? null : slot_calculator::day_key($to),
    'status' => $status,
    'user' => $usertext,
], $download);
if ($table->is_downloading()) {
    $table->out(0, false);
    die();
}

ob_start();
$table->out(20, true);
$tablehtml = ob_get_clean();

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_tipbooking/space_bookings', [
    // The template escapes the title itself.
    'title' => get_string('bookingsofnamed', 'local_tipbooking', space_summary::name($space, false)),
    'zonelabel' => get_string('spacebookings:zone', 'local_tipbooking', $zone),
    'editurl' => (new url('/local/tipbooking/edit.php', ['id' => $space->id]))->out(false),
    'spaceurl' => (new url('/local/tipbooking/space.php', ['id' => $space->id]))->out(false),
    'filterform' => $form->render(),
    'table' => $tablehtml,
]);
echo $OUTPUT->footer();
