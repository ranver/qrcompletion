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
 * QR Code validation page for QR Completion plugin.
 *
 * @package    local_qrcompletion
 * @copyright  2024 Randy Vermaas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url('/local/qrcompletion/validate_qr.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title('QR Code Validation');
$PAGE->set_heading('QR Code Validation');

// Include the new CSS file for QR validation.
$PAGE->requires->css('/local/qrcompletion/css/qrvalidation.css');
// Include JS file.
$PAGE->requires->js('/local/qrcompletion/js/qrvalidation.js');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Scan and Validate QR Code');

// Ensure the correct path to the html5-qrcode library.
echo html_writer::script('', $CFG->wwwroot . '/local/qrcompletion/html5-qrcode/html5-qrcode.min.js');

// Hidden field to store the course ID.
echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'course-id', 'value' => $courseid]);

// HTML5 QR Code Scanner and Result Image.
echo html_writer::start_tag('div', ['class' => 'qr-scan-container']);
echo html_writer::start_tag('div', ['id' => 'qr-reader', 'class' => 'qr-scanner']);
echo html_writer::end_tag('div');
echo html_writer::tag('div', '', ['id' => 'qr-reader-results']);
echo html_writer::end_tag('div');

// Output the footer.
echo $OUTPUT->footer();
