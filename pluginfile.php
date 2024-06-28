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
 * Script to serve files for QR Completion plugin.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');

$contextid = required_param('contextid', PARAM_INT);
$component = required_param('component', PARAM_COMPONENT);
$filearea = required_param('filearea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$filepath = required_param('filepath', PARAM_PATH);
$filename = required_param('filename', PARAM_FILE);

$context = context::instance_by_id($contextid);
require_login();

if ($context->contextlevel != CONTEXT_SYSTEM) {
    send_file_not_found();
}

$fs = get_file_storage();
$file = $fs->get_file($contextid, $component, $filearea, $itemid, $filepath, $filename);

if (!$file || $file->is_directory()) {
    send_file_not_found();
}

send_stored_file($file, 86400, 0, false, ['filename' => $filename]);
