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

$pix_url = new moodle_url('/local/qrcompletion/pix');

$tokens = explode('|', $qrcode);
if (count($tokens) != 3) {
    echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_invalid.png" alt="Invalid QR Code" style="margin-top: 10px;"><br>Invalid QR Code format.</div>';
    error_log("Invalid QR Code format. QR code: $qrcode", 3, $CFG->dataroot . '/qrcompletion_logs.log');
    die();
}

$securetoken = trim($tokens[0]);
$timestamp = trim($tokens[1]);
$userid = trim($tokens[2]);

$currenttime = time();
$log_message = "Debug Info:\nSecure Token: $securetoken\nTimestamp: $timestamp\nUserid: $userid\nCourseid: $courseid\nCurrent Time: $currenttime\n";

if (($currenttime - $timestamp) > 300) { // Token valid for 5 minutes.
    echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_expired.png" alt="QR Code Expired" style="margin-top: 10px;"><br>QR Code has expired.</div>';
    error_log($log_message . "QR Code has expired.\n", 3, $CFG->dataroot . '/qrcompletion_logs.log');
    $DB->delete_records_select('local_qrcompletion_tokens', 'timestamp < ?', [$currenttime - 300]);
    die();
}

$secret = 'your-secret-key'; // Use the same secure secret key.

try {
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

    if (!$record) {
        throw new Exception('Invalid QR Code.');
    }

    $token_data = $userid . '|' . $record->courseid . '|' . $timestamp;
    $expectedtoken = hash_hmac('sha256', $token_data, $secret);
    $log_message .= "Token Data: $token_data\nExpected Token: $expectedtoken\n";

    if (!hash_equals($expectedtoken, $securetoken)) {
        echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_invalid.png" alt="Invalid QR Code" style="margin-top: 10px;"><br>Invalid QR Code.</div>';
        error_log($log_message . "Token mismatch.\nExpected Token: $expectedtoken\nSecure Token: $securetoken\n", 3, $CFG->dataroot . '/qrcompletion_logs.log');
        die();
    }

    if ($record->courseid != $courseid) {
        $coursename = $DB->get_field('course', 'fullname', ['id' => $record->courseid]);
        echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_wrong_course.png" alt="Wrong Course" style="margin-top: 10px;"><br>QR Code is valid, but for another course: ' . $coursename . '.</div>';
        error_log($log_message . "QR Code is valid, but for another course: $coursename\n", 3, $CFG->dataroot . '/qrcompletion_logs.log');
        die();
    }

    $student = $DB->get_record('user', ['id' => $userid]);
    if (!$student) {
        echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_invalid.png" alt="Invalid QR Code" style="margin-top: 10px;"><br>Student not found.</div>';
        error_log($log_message . "Student not found.\n", 3, $CFG->dataroot . '/qrcompletion_logs.log');
        die();
    }
    $student_name = fullname($student);

    echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_valid.png" alt="Access Granted" style="margin-top: 10px;"><br>QR Code is valid. Access granted for ' . $student_name . '.</div>';
    error_log($log_message . "QR Code validated. Access granted for $student_name.\n", 3, $CFG->dataroot . '/qrcompletion_logs.log');

} catch (Exception $e) {
    error_log("Error: " . $e->getMessage(), 3, $CFG->dataroot . '/qrcompletion_logs.log');
    echo '<div id="qr-result-image"><img src="' . $pix_url . '/qr_invalid.png" alt="Error" style="margin-top: 10px;"><br>An error occurred: ' . $e->getMessage() . '</div>';
}
