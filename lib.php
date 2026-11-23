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
 * Callbacks of local_tipbooking.
 *
 * @package    local_tipbooking
 * @copyright  2026 3iPunt (contacte@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_tipbooking\space_manager;

/**
 * Serves the images of the spaces to signed-in people who can see the spaces.
 *
 * @param  stdClass $course        Course of the request (the site).
 * @param  stdClass|null $cm       Course module, unused.
 * @param  context  $context       Context of the file.
 * @param  string   $filearea      File area.
 * @param  array    $args          Item id, then path and file name.
 * @param  bool     $forcedownload Whether the file is sent as a download.
 * @param  array    $options       Extra options of send_stored_file().
 * @return bool                    False when the file cannot be served.
 */
function local_tipbooking_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    if ($context->contextlevel !== CONTEXT_SYSTEM || $filearea !== space_manager::FILEAREA_IMAGE) {
        return false;
    }

    require_login(null, false);
    if (isguestuser() || !has_capability('local/tipbooking:view', $context)) {
        return false;
    }

    $itemid = (int) array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $file = get_file_storage()->get_file($context->id, 'local_tipbooking', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, DAYSECS, 0, $forcedownload, $options);
}
