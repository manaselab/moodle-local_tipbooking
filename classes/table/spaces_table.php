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
 * Table of the spaces, for their managers.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\table;

use context_system;
use core\output\html_writer;
use core\output\pix_icon;
use core\url;
use core_table\sql_table;
use core_user\fields;
use local_tipbooking\models\booking;
use local_tipbooking\models\space;
use local_tipbooking\space_manager;
use stdClass;

/**
 * Lists every space with its period, hours, managers and upcoming bookings.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class spaces_table extends sql_table {
    /** @var int How many managers are named before "and N more". */
    private const ADMINS_SHOWN = 2;

    /** @var array Space id => names of its managers, for the current page. */
    private array $adminnames = [];

    /**
     * Constructor.
     *
     * @param url    $baseurl  Address of the page that shows the table.
     * @param string $download Format to download the table in, or empty to show it.
     */
    public function __construct(url $baseurl, string $download = '') {
        parent::__construct('local_tipbooking_spaces');
        $this->is_downloading($download, 'spaces', get_string('managespaces', 'local_tipbooking'));

        $columns = ['name', 'period', 'hours', 'slotduration', 'weekdays', 'admins', 'futurebookings', 'visible'];
        $headers = [
            get_string('table:space', 'local_tipbooking'),
            get_string('table:period', 'local_tipbooking'),
            get_string('table:hours', 'local_tipbooking'),
            get_string('table:slot', 'local_tipbooking'),
            get_string('table:days', 'local_tipbooking'),
            get_string('table:admins', 'local_tipbooking'),
            get_string('table:futurebookings', 'local_tipbooking'),
            get_string('table:visible', 'local_tipbooking'),
        ];
        if (!$this->is_downloading()) {
            $columns[] = 'actions';
            $headers[] = get_string('actions');
        }
        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->define_baseurl($baseurl);

        $this->sortable(true, 'name', SORT_ASC);
        $this->no_sorting('hours');
        $this->no_sorting('weekdays');
        $this->no_sorting('admins');
        $this->no_sorting('actions');
        $this->collapsible(false);
        $this->is_downloadable(true);
        $this->show_download_buttons_at([TABLE_P_BOTTOM]);

        $this->set_sql(
            's.*, (SELECT COUNT(1)
                     FROM {' . booking::TABLE . '} b
                    WHERE b.spaceid = s.id AND b.status = :active AND b.timestart > :now) AS futurebookings,
                  s.datefrom AS period',
            '{' . space::TABLE . '} s',
            '1 = 1',
            ['active' => booking::STATUS_ACTIVE, 'now' => time()]
        );
        $this->set_count_sql('SELECT COUNT(1) FROM {' . space::TABLE . '}');
    }

    /**
     * Loads the page of rows and the managers of their spaces in one more query.
     *
     * @param  int  $pagesize   Rows per page.
     * @param  bool $useinitialsbar Whether the initials bar is used.
     * @return void
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        parent::query_db($pagesize, $useinitialsbar);

        $ids = array_map(fn($row) => (int) $row->id, $this->rawdata);
        $this->adminnames = [];
        if ($ids === []) {
            return;
        }

        [$insql, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED);
        $namefields = fields::for_name()->get_sql('u')->selects;
        $records = $DB->get_recordset_sql(
            "SELECT a.id AS adminrowid, a.spaceid, u.id $namefields
               FROM {" . space_manager::ADMIN_TABLE . "} a
               JOIN {user} u ON u.id = a.userid
              WHERE a.spaceid $insql",
            $params
        );
        foreach ($records as $record) {
            $this->adminnames[(int) $record->spaceid][] = fullname($record);
        }
        $records->close();
    }

    /**
     * Dims the rows of hidden spaces.
     *
     * @param  stdClass $row The row.
     * @return string        CSS class of the row.
     */
    public function get_row_class($row) {
        return $row->visible ? '' : 'dimmed_text';
    }

    /**
     * Name of the space, with its image.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_name(stdClass $row): string {
        $name = format_string($row->name, true, ['context' => context_system::instance()]);
        if ($this->is_downloading()) {
            return $name;
        }

        $image = space_manager::get_image_url((int) $row->id);
        $thumb = $image
            ? html_writer::img($image->out(false), '', ['class' => 'local_tipbooking-thumb'])
            : html_writer::span('', 'local_tipbooking-thumb local_tipbooking-thumb-empty');

        return html_writer::div($thumb . html_writer::span($name, 'fw-semibold'), 'd-flex align-items-center gap-2');
    }

    /**
     * Bookable period, in the time zone of the space.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_period(stdClass $row): string {
        $zone = space::from_record($row)->get_timezone()->getName();
        $format = get_string('strftimedatefullshort', 'langconfig');
        return userdate((int) $row->datefrom, $format, $zone) . ' – ' . userdate((int) $row->dateto, $format, $zone);
    }

    /**
     * Opening hours and time zone.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_hours(stdClass $row): string {
        return get_string('table:hoursvalue', 'local_tipbooking', (object) [
            'from' => self::clock((int) $row->hourfrom),
            'to' => self::clock((int) $row->hourto),
            'timezone' => space::from_record($row)->get_timezone()->getName(),
        ]);
    }

    /**
     * Length of the slots.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_slotduration(stdClass $row): string {
        return get_string('form:durationhours', 'local_tipbooking', round((int) $row->slotduration / 60, 2));
    }

    /**
     * Bookable weekdays.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_weekdays(stdClass $row): string {
        $space = space::from_record($row);
        $days = [];
        for ($day = 1; $day <= 7; $day++) {
            if ($space->is_weekday_bookable($day)) {
                $days[] = get_string('form:weekdayshort' . $day, 'local_tipbooking');
            }
        }
        return implode(', ', $days);
    }

    /**
     * Managers of the space.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_admins(stdClass $row): string {
        $names = $this->adminnames[(int) $row->id] ?? [];
        if ($this->is_downloading() || count($names) <= self::ADMINS_SHOWN) {
            return s(implode(', ', $names));
        }
        return s(get_string('table:adminsmore', 'local_tipbooking', (object) [
            'names' => implode(', ', array_slice($names, 0, self::ADMINS_SHOWN)),
            'more' => count($names) - self::ADMINS_SHOWN,
        ]));
    }

    /**
     * Upcoming bookings, linked to the bookings of the space.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_futurebookings(stdClass $row): string {
        if ($this->is_downloading()) {
            return (string) (int) $row->futurebookings;
        }
        return html_writer::link(
            new url('/local/tipbooking/bookings.php', ['spaceid' => $row->id]),
            (int) $row->futurebookings
        );
    }

    /**
     * Whether the space is shown in the showcase.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_visible(stdClass $row): string {
        return $row->visible ? get_string('yes') : get_string('no');
    }

    /**
     * Edit, bookings, hide or show, and delete.
     *
     * @param  stdClass $row The row.
     * @return string        The cell.
     */
    public function col_actions(stdClass $row): string {
        global $OUTPUT;

        $manage = '/local/tipbooking/manage.php';
        // The icons escape their title themselves.
        $name = format_string($row->name, true, ['context' => context_system::instance(), 'escape' => false]);
        $visibility = $row->visible ? 'hide' : 'show';

        return $OUTPUT->action_icon(
                new url('/local/tipbooking/edit.php', ['id' => $row->id]),
                new pix_icon('t/edit', get_string('editspacenamed', 'local_tipbooking', $name))
            )
            . $OUTPUT->action_icon(
                new url('/local/tipbooking/bookings.php', ['spaceid' => $row->id]),
                new pix_icon('i/calendar', get_string('bookingsofnamed', 'local_tipbooking', $name))
            )
            . $OUTPUT->action_icon(
                new url($manage, ['action' => $visibility, 'id' => $row->id, 'sesskey' => sesskey()]),
                new pix_icon('t/' . $visibility, get_string($visibility . 'spacenamed', 'local_tipbooking', $name))
            )
            . $OUTPUT->action_icon(
                new url($manage, ['action' => 'delete', 'id' => $row->id]),
                new pix_icon('t/delete', get_string('deletespacenamed', 'local_tipbooking', $name))
            );
    }

    /**
     * Minutes from midnight as HH:MM.
     *
     * @param  int    $minutes Minutes from midnight.
     * @return string          The time.
     */
    private static function clock(int $minutes): string {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
