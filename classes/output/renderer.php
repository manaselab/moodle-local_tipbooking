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
 * Renderer of local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_tipbooking\output;

use core\output\plugin_renderer_base;

/**
 * Renders the pages of the plugin from their templates.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {
    /**
     * Renders the showcase of spaces.
     *
     * @param  catalogue $catalogue The showcase.
     * @return string               The HTML.
     */
    protected function render_catalogue(catalogue $catalogue): string {
        return $this->render_from_template('local_tipbooking/catalogue', $catalogue->export_for_template($this));
    }

    /**
     * Renders the page of a space.
     *
     * @param  space_page $page The page.
     * @return string           The HTML.
     */
    protected function render_space_page(space_page $page): string {
        return $this->render_from_template('local_tipbooking/space_page', $page->export_for_template($this));
    }
}
