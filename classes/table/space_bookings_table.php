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

namespace local_tipbooking\table;

use core\output\html_writer;
use core\output\user_picture;
use core\url;
use core_table\sql_table;
use core_user\fields;
use local_tipbooking\form\space_bookings_filter_form;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\output\my_bookings;
use local_tipbooking\output\space_page;
use stdClass;

/**
 * Bookings of one space, for its managers: who booked which range, and their state.
 *
 * Days and times are written in the time zone of the space.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class space_bookings_table extends sql_table {
    /** @var string Prefix of the fields of the person who booked. */
    private const USER_PREFIX = 'booker';

    /** @var array User id => full name of who cancelled, for the current page. */
    private array $cancellers = [];

    /** @var int The current moment. */
    private int $now;

    /**
     * Constructor.
     *
     * @param space  $space    The space.
     * @param url    $baseurl  Address of the page that shows the table, with its filters.
     * @param array  $filters  'from' and 'to' (days as YYYYMMDD, or null), 'status' (one of the
     *                         STATUS_ constants of the filter form) and 'user' (text).
     * @param string $download Format to download the table in, or empty to show it.
     */
    public function __construct(
        /** @var space The space. */
        private readonly space $space,
        url $baseurl,
        array $filters,
        string $download = ''
    ) {
        global $DB;

        parent::__construct('local_tipbooking_space_bookings_' . $space->id);
        $this->now = time();
        $name = format_string($space->name, true, ['context' => \context_system::instance()]);
        $this->is_downloading(
            $download,
            clean_filename('bookings-' . $name),
            get_string('bookingsofnamed', 'local_tipbooking', $name)
        );

        $columns = ['timestart', 'range', 'bookername', 'status', 'timecreated', 'timecancelled'];
        $headers = [
            get_string('table:day', 'local_tipbooking'),
            get_string('table:range', 'local_tipbooking'),
            get_string('table:user', 'local_tipbooking'),
            get_string('table:status', 'local_tipbooking'),
            get_string('table:bookedon', 'local_tipbooking'),
            get_string('table:cancelled', 'local_tipbooking'),
        ];
        if (!$this->is_downloading()) {
            $columns[] = 'actions';
            $headers[] = get_string('actions');
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);

        $this->sortable(true, 'timestart', SORT_ASC);
        $this->no_sorting('range');
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        [$where, $params] = self::filter_sql($space->id, $filters);
        $userfields = fields::for_userpic()->get_sql('u', false, self::USER_PREFIX, self::USER_PREFIX . 'id', false)->selects;
        $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
        $this->set_sql(
            "b.*, $fullname AS bookername, $userfields",
            '{' . booking::TABLE . '} b JOIN {user} u ON u.id = b.userid',
            $where,
            $params
        );
        $this->set_count_sql(
            'SELECT COUNT(1) FROM {' . booking::TABLE . '} b JOIN {user} u ON u.id = b.userid WHERE ' . $where,
            $params
        );
    }

    /**
     * Condition of the bookings of a space that match some filters.
     *
     * @param  int   $spaceid Identifier of the space.
     * @param  array $filters As in the constructor.
     * @return array          [where, params], over booking b and user u.
     */
    public static function filter_sql(int $spaceid, array $filters): array {
        global $DB;

        $where = ['b.spaceid = :spaceid'];
        $params = ['spaceid' => $spaceid];
        if (!empty($filters['from'])) {
            $where[] = 'b.day >= :fromday';
            $params['fromday'] = (int) $filters['from'];
        }
        if (!empty($filters['to'])) {
            $where[] = 'b.day <= :today';
            $params['today'] = (int) $filters['to'];
        }
        $status = $filters['status'] ?? space_bookings_filter_form::STATUS_ALL;
        if ($status === space_bookings_filter_form::STATUS_ACTIVE) {
            $where[] = 'b.status = :status';
            $params['status'] = booking::STATUS_ACTIVE;
        } else if ($status === space_bookings_filter_form::STATUS_CANCELLED) {
            $where[] = 'b.status = :status';
            $params['status'] = booking::STATUS_CANCELLED;
        }
        $text = trim((string) ($filters['user'] ?? ''));
        if ($text !== '') {
            $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
            $where[] = '(' . $DB->sql_like($fullname, ':username', false, false)
                . ' OR ' . $DB->sql_like('u.email', ':useremail', false, false) . ')';
            $params['username'] = '%' . $DB->sql_like_escape($text) . '%';
            $params['useremail'] = '%' . $DB->sql_like_escape($text) . '%';
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Loads the page of rows and the names of who cancelled them in one more query.
     *
     * @param  int  $pagesize       Rows per page.
     * @param  bool $useinitialsbar Whether the initials bar is used.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        parent::query_db($pagesize, $useinitialsbar);

        $ids = array_unique(array_filter(array_map(fn($row) => (int) $row->cancelledby, $this->rawdata)));
        $this->cancellers = [];
        if ($ids === []) {
            return;
        }
        $namefields = fields::for_name()->get_sql('u')->selects;
        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        foreach ($DB->get_records_sql("SELECT u.id $namefields FROM {user} u WHERE u.id $insql", $params) as $user) {
            $this->cancellers[(int) $user->id] = fullname($user);
        }
    }

    /**
     * Shows the empty state of the design instead of the generic one.
     *
     * @return void
     */
    public function print_nothing_to_display() {
        global $OUTPUT;
        echo $OUTPUT->notification(get_string('spacebookings:empty', 'local_tipbooking'), 'info', false);
    }

    /**
     * Dims cancelled rows.
     *
     * @param  stdClass $row The row.
     * @return string        CSS class of the row.
     */
    public function get_row_class($row) {
        return (int) $row->status === booking::STATUS_ACTIVE ? '' : 'text-muted';
    }

    /**
     * Day of the booking.
     *
     * @param  stdClass $row The row.
     * @return string        The day.
     */
    public function col_timestart(stdClass $row): string {
        return userdate($row->timestart, get_string('strftimedaydate', 'langconfig'), $this->zone());
    }

    /**
     * Range and its length.
     *
     * @param  stdClass $row The row.
     * @return string        "10:00–12:00 · 2 h".
     */
    public function col_range(stdClass $row): string {
        return space_page::range_label((int) $row->timestart, (int) $row->timeend, $this->zone())
            . ' · ' . my_bookings::duration((int) $row->timeend - (int) $row->timestart);
    }

    /**
     * Who booked, with picture and a link to the profile on screen.
     *
     * @param  stdClass $row The row.
     * @return string        The person.
     */
    public function col_bookername(stdClass $row): string {
        global $OUTPUT;

        $user = user_picture::unalias($row, null, self::USER_PREFIX . 'id', self::USER_PREFIX);
        if ($this->is_downloading()) {
            return fullname($user);
        }
        return $OUTPUT->user_picture($user, ['size' => 24, 'includefullname' => true]);
    }

    /**
     * State of the booking.
     *
     * @param  stdClass $row The row.
     * @return string        The state; on screen, as a badge the cancel button can change.
     */
    public function col_status(stdClass $row): string {
        if ((int) $row->status !== booking::STATUS_ACTIVE) {
            $status = 'cancelled';
        } else if ($row->timeend <= $this->now) {
            $status = 'finished';
        } else if ($row->timestart <= $this->now) {
            $status = 'inprogress';
        } else {
            $status = 'confirmed';
        }
        $label = get_string('mybookings:status' . $status, 'local_tipbooking');
        if ($this->is_downloading()) {
            return $label;
        }
        $class = match ($status) {
            'confirmed' => 'bg-success',
            'inprogress' => 'bg-primary',
            default => 'bg-secondary text-dark',
        };
        return html_writer::span($label, 'badge ' . $class, ['data-region' => 'local_tipbooking-status']);
    }

    /**
     * When it was booked.
     *
     * @param  stdClass $row The row.
     * @return string        The moment.
     */
    public function col_timecreated(stdClass $row): string {
        return userdate($row->timecreated, get_string('strftimedatetimeshort', 'langconfig'), $this->zone());
    }

    /**
     * Who cancelled it and when, if it was cancelled.
     *
     * @param  stdClass $row The row.
     * @return string        "Name · moment", or a dash.
     */
    public function col_timecancelled(stdClass $row): string {
        if ((int) $row->status === booking::STATUS_ACTIVE) {
            return '—';
        }
        $who = $this->cancellers[(int) $row->cancelledby] ?? '';
        $when = userdate($row->timecancelled, get_string('strftimedatetimeshort', 'langconfig'), $this->zone());
        return $who === '' ? $when : $who . ' · ' . $when;
    }

    /**
     * "Cancel" on active bookings that have not ended, as the managers may cancel them.
     *
     * @param  stdClass $row The row.
     * @return string        The button, or nothing.
     */
    public function col_actions(stdClass $row): string {
        if ((int) $row->status !== booking::STATUS_ACTIVE || $row->timeend <= $this->now) {
            return '';
        }
        $user = user_picture::unalias($row, null, self::USER_PREFIX . 'id', self::USER_PREFIX);
        $range = space_page::range_label((int) $row->timestart, (int) $row->timeend, $this->zone());
        return html_writer::tag('button', get_string('mybookings:cancel', 'local_tipbooking'), [
            'type' => 'button',
            'class' => 'btn btn-outline-danger btn-sm',
            'data-action' => 'local_tipbooking-cancel',
            'data-bookingid' => $row->id,
            'data-label' => $range,
            'data-daylabel' => $this->col_timestart($row),
            'data-user' => fullname($user),
            'aria-label' => get_string('spacebookings:cancelnamed', 'local_tipbooking', (object) [
                'user' => fullname($user),
                'range' => $range,
            ]),
        ]);
    }

    /**
     * Time zone of the space.
     *
     * @return string The time zone.
     */
    private function zone(): string {
        return $this->space->get_timezone()->getName();
    }
}
