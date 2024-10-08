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
 * QR code serving script for QR Completion plugin.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login(); // Ensure the user is logged in.

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT); // Add course ID parameter.

$context = context_course::instance($courseid); // Check in course context.
require_capability('local/qrcompletion:view', $context);

$filepath = $CFG->dataroot . '/qrcodes/' . $userid . '.png';

// Debugging output.
if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found: " . $filepath;
    die();
}

header('Content-Type: image/png');
readfile($filepath);
exit;
