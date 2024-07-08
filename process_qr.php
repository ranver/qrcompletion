<?php
// This file is part of Moodle - http://moodle.org/.
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
 * QR Code validation script for QR Completion plugin.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$qrcode = required_param('qrcode', PARAM_TEXT);
$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:manageactivities', $context);

$pixurl = new moodle_url('/local/qrcompletion/pix');

$tokens = explode('|', $qrcode);
if (count($tokens) != 3) {
    echo '<img src="' . $pixurl . '/qr_invalid.png" alt="Invalid QR Code"><br>Invalid QR Code format.';
    die();
}

$securetoken = trim($tokens[0]);
$timestamp = trim($tokens[1]);
$userid = trim($tokens[2]);

$currenttime = time();
if (($currenttime - $timestamp) > 300) { // Token valid for 5 minutes.
    echo '<img src="' . $pixurl . '/qr_expired.png" alt="QR Code Expired"><br>QR Code has expired.';
    $DB->delete_records_select('local_qrcompletion_tokens', 'timestamp < ?', [$currenttime - 300]);
    die();
}

$secret = 'your-secret-key'; // Use the same secure secret key.

$sql = "SELECT * FROM {local_qrcompletion_tokens}
        WHERE userid = :userid
          AND token = :token
          AND timestamp = :timestamp";
$params = [
    'userid' => $userid,
    'token' => $securetoken,
    'timestamp' => $timestamp,
];
$record = $DB->get_record_sql($sql, $params);

if ($record) {
    $tokendata = $userid . '|' . $record->courseid . '|' . $timestamp;
    $expectedtoken = hash_hmac('sha256', $tokendata, $secret);

    if (!hash_equals($expectedtoken, $securetoken)) {
        echo '<img src="' . $pixurl . '/qr_invalid.png" alt="Invalid QR Code"><br>Invalid QR Code.';
        die();
    }

    if ($record->courseid != $courseid) {
        $coursename = $DB->get_field('course', 'fullname', ['id' => $record->courseid]);
        echo '<img src="' . $pixurl . '/qr_wrong_course.png" alt="Wrong Course"><br>'
            . 'QR Code is valid, but for another course: ' . $coursename . '.';
        die();
    }
    $student = $DB->get_record('user', ['id' => $userid]);
    if (!$student) {
        echo '<img src="' . $pixurl . '/qr_invalid.png" alt="Invalid QR Code"><br>Student not found.';
        die();
    }
    $studentname = fullname($student);

    echo '<img src="' . $pixurl . '/qr_valid.png" alt="Access Granted"><br>'
        . 'QR Code is valid. Access granted for ' . $studentname . '.';

} else {
    echo '<img src="' . $pixurl . '/qr_invalid.png" alt="Invalid QR Code"><br>Invalid QR Code.';
    $allrecords = $DB->get_records('local_qrcompletion_tokens', ['userid' => $userid]);
}
