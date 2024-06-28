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

$tokens = explode('|', $qrcode);
if (count($tokens) != 3) {
    echo 'Invalid QR Code format.<br>';
    die();
}

$securetoken = trim($tokens[0]);
$timestamp = trim($tokens[1]);
$userid = trim($tokens[2]);

// Debugging output.
echo "Secure Token from QR code: '$securetoken'<br>";
echo "Timestamp from QR code: '$timestamp'<br>";
echo "User ID from QR code: '$userid'<br>";

// Validate the token (ensure it hasn't expired).
$currenttime = time();
echo "Current Time: '$currenttime'<br>";
echo "Time Difference: '" . ($currenttime - $timestamp) . "' seconds<br>";

if (($currenttime - $timestamp) > 300) { // Token valid for 5 minutes.
    echo 'QR Code has expired.<br>';
    // Delete expired tokens.
    $DB->delete_records_select('local_qrcompletion_tokens', 'timestamp < ?', [$currenttime - 300]);
    die();
}

// Validate the token.
$secret = 'your-secret-key'; // Use the same secure secret key.
$expectedtoken = hash_hmac('sha256', $userid . '|' . $courseid . '|' . $timestamp, $secret);

// Debugging output.
echo "Expected Token: '$expectedtoken'<br>";

if (!hash_equals($expectedtoken, $securetoken)) {
    echo 'Invalid QR Code.<br>';
    die();
}

// Fetch the specific record.
$sql = "SELECT * FROM {local_qrcompletion_tokens}
        WHERE userid = :userid
          AND courseid = :courseid
          AND token = :token
          AND timestamp = :timestamp";
$params = [
    'userid' => $userid,
    'courseid' => $courseid,
    'token' => $securetoken,
    'timestamp' => $timestamp,
];
$record = $DB->get_record_sql($sql, $params);

// Debugging information.
echo "SQL Query: $sql<br>";
echo "Parameters: <br>";
echo "UserID: '$userid'<br>";
echo "CourseID: '$courseid'<br>";
echo "Token: '$securetoken'<br>";
echo "Timestamp: '$timestamp'<br>";

// Log the query.
$logmessage = "SQL Query: $sql\nParameters: " . var_export($params, true) . "\n";
file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $logmessage, FILE_APPEND);

if ($record) {
    echo 'QR Code is valid and has been used.<br>';
    echo "Record from DB:<br>";
    echo "Token: '$record->token'<br>";
    echo "Timestamp: '$record->timestamp'<br>";
    // Compare the raw values directly.
    echo "Raw Token Comparison: '" . strcmp($securetoken, $record->token) . "'<br>";
    echo "Raw Timestamp Comparison: '" . strcmp($timestamp, $record->timestamp) . "'<br>";

    // Log the found record.
    $logmessage = "Record found: " . var_export($record, true) . "\n";
    file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $logmessage, FILE_APPEND);

} else {
    echo 'Invalid QR Code.<br>';
    // Retrieve and display all tokens for further debugging.
    $allrecords = $DB->get_records('local_qrcompletion_tokens', ['userid' => $userid, 'courseid' => $courseid]);
    $logmessage = "All tokens for user '$userid' in course '$courseid': " . var_export($allrecords, true) . "\n";
    file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $logmessage, FILE_APPEND);
    echo $logmessage; // Optionally display the message for debugging.
}
