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
 * Showcase of spaces.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use local_tipbooking\models\space;

/**
 * The showcase: each visible space once, as a card, with the filters above.
 *
 * Everything it shows arrives through the constructor; it reads nothing by itself.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class catalogue implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param space[]     $spaces      Spaces of the current page.
     * @param int         $total       How many spaces match the filters.
     * @param array       $imageurls   Space id => address of its image, or null.
     * @param array       $mycounts    Space id => upcoming bookings of the reader there.
     * @param int         $myupcoming  Upcoming bookings of the reader, in all spaces.
     * @param bool        $canmanage   Whether the reader can manage spaces.
     * @param bool        $filtered    Whether any filter is applied.
     * @param string|null $datelabel   The day of the "free on" filter, ready to print, or null.
     * @param string      $filterform  The filter form, already rendered.
     * @param string      $pagingbar   The paging bar, already rendered.
     */
    public function __construct(
        /** @var space[] Spaces of the current page. */
        private readonly array $spaces,
        /** @var int How many spaces match the filters. */
        private readonly int $total,
        /** @var array Space id => address of its image, or null. */
        private readonly array $imageurls,
        /** @var array Space id => upcoming bookings of the reader there. */
        private readonly array $mycounts,
        /** @var int Upcoming bookings of the reader, in all spaces. */
        private readonly int $myupcoming,
        /** @var bool Whether the reader can manage spaces. */
        private readonly bool $canmanage,
        /** @var bool Whether any filter is applied. */
        private readonly bool $filtered,
        /** @var string|null The day of the "free on" filter, ready to print, or null. */
        private readonly ?string $datelabel,
        /** @var string The filter form, already rendered. */
        private readonly string $filterform,
        /** @var string The paging bar, already rendered. */
        private readonly string $pagingbar,
    ) {
    }

    /**
     * Data for the template.
     *
     * @param  renderer_base $output The renderer.
     * @return array                 The context of local_tipbooking/catalogue.
     */
    public function export_for_template(renderer_base $output): array {
        $cards = [];
        foreach ($this->spaces as $space) {
            $image = $this->imageurls[$space->id] ?? null;
            $mine = (int) ($this->mycounts[$space->id] ?? 0);
            $cards[] = [
                'name' => space_summary::name($space),
                'excerpt' => space_summary::excerpt($space),
                'url' => (new url('/local/tipbooking/space.php', ['id' => $space->id]))->out(false),
                'imageurl' => $image instanceof url ? $image->out(false) : '',
                'hasimage' => $image instanceof url,
                'period' => space_summary::period($space),
                'hours' => space_summary::hours($space),
                'slot' => space_summary::slot($space),
                'days' => space_summary::days($space),
                'mybookings' => $mine,
                'hasmybookings' => $mine > 0,
            ];
        }

        if ($this->datelabel !== null) {
            $summary = get_string('catalogue:countondate', 'local_tipbooking',
                (object) ['count' => $this->total, 'date' => $this->datelabel]);
        } else {
            $summary = get_string('catalogue:count', 'local_tipbooking', $this->total);
        }

        return [
            'cards' => $cards,
            'hascards' => $cards !== [],
            'summary' => $summary,
            'filtered' => $this->filtered,
            'nospaces' => $this->total === 0 && !$this->filtered,
            'noresults' => $this->total === 0 && $this->filtered,
            'clearurl' => (new url('/local/tipbooking/index.php'))->out(false),
            'mybookingsurl' => (new url('/local/tipbooking/mybookings.php'))->out(false),
            'myupcoming' => $this->myupcoming,
            'canmanage' => $this->canmanage,
            'manageurl' => (new url('/local/tipbooking/manage.php'))->out(false),
            'newspaceurl' => (new url('/local/tipbooking/edit.php'))->out(false),
            'filterform' => $this->filterform,
            'pagingbar' => $this->pagingbar,
        ];
    }
}
