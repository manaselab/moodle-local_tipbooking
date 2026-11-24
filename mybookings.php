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
 * Bookings of the current user: upcoming (default) or past, grouped by day.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\output\paging_bar;
use core\url;
use local_tipbooking\booking_manager;
use local_tipbooking\models\space;
use local_tipbooking\output\my_bookings;
use local_tipbooking\space_manager;

require_once(__DIR__ . '/../../config.php');

/** @var int Bookings per page. */
const LOCAL_TIPBOOKING_MYPERPAGE = 20;

require_login(null, false);
if (isguestuser()) {
    redirect(get_login_url());
}
$context = context_system::instance();
require_capability('local/tipbooking:view', $context);

$tab = optional_param('tab', 'upcoming', PARAM_ALPHA);
$upcoming = $tab !== 'past';
$tab = $upcoming ? 'upcoming' : 'past';
$page = optional_param('page', 0, PARAM_INT);

$baseurl = new url('/local/tipbooking/mybookings.php');
$taburl = new url($baseurl, ['tab' => $tab]);

$PAGE->set_context($context);
$PAGE->set_url(new url($taburl, $page ? ['page' => $page] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('mybookings', 'local_tipbooking'));
$PAGE->set_heading(get_string('mybookings', 'local_tipbooking'));
$PAGE->navbar->add(get_string('catalogue:title', 'local_tipbooking'), new url('/local/tipbooking/index.php'));
$PAGE->navbar->add(get_string('mybookings', 'local_tipbooking'), $baseurl);

$userid = (int) $USER->id;
$total = booking_manager::count_user_bookings($userid, $upcoming);
$bookings = booking_manager::get_user_bookings(
    $userid,
    $upcoming,
    LOCAL_TIPBOOKING_MYPERPAGE,
    $page * LOCAL_TIPBOOKING_MYPERPAGE
);

$spaces = [];
$imageurls = [];
$spaceids = array_unique(array_map(fn($booking) => $booking->spaceid, $bookings));
if ($spaceids) {
    foreach ($DB->get_records_list(space::TABLE, 'id', $spaceids) as $record) {
        $spaces[$record->id] = space::from_record($record);
        $imageurls[$record->id] = space_manager::get_image_url((int) $record->id);
    }
}

$tabs = [
    new tabobject('upcoming', new url($baseurl, ['tab' => 'upcoming']), get_string(
        'mybookings:upcomingcount',
        'local_tipbooking',
        $upcoming ? $total : booking_manager::count_user_bookings($userid, true)
    )),
    new tabobject('past', new url($baseurl, ['tab' => 'past']), get_string('mybookings:past', 'local_tipbooking')),
];

$list = new my_bookings(
    $bookings,
    $spaces,
    $imageurls,
    $upcoming,
    $OUTPUT->tabtree($tabs, $tab),
    $OUTPUT->render(new paging_bar($total, $page, LOCAL_TIPBOOKING_MYPERPAGE, $taburl))
);

echo $OUTPUT->header();
echo $PAGE->get_renderer('local_tipbooking')->render($list);
echo $OUTPUT->footer();
