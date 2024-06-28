<?php
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

$secureToken = trim($tokens[0]);
$timestamp = trim($tokens[1]);
$userid = trim($tokens[2]);

// Debugging output
echo "Secure Token from QR code: '$secureToken'<br>";
echo "Timestamp from QR code: '$timestamp'<br>";
echo "User ID from QR code: '$userid'<br>";

// Validate the token (ensure it hasn't expired)
$current_time = time();
echo "Current Time: '$current_time'<br>";
echo "Time Difference: '" . ($current_time - $timestamp) . "' seconds<br>";

if (($current_time - $timestamp) > 300) { // Token valid for 5 minutes
    echo 'QR Code has expired.<br>';
    // Delete expired tokens
    $DB->delete_records_select('local_qrcompletion_tokens', 'timestamp < ?', [$current_time - 300]);
    die();
}

// Fetch the specific record
$sql = "SELECT * FROM {local_qrcompletion_tokens} WHERE userid = :userid AND courseid = :courseid AND token = :token AND timestamp = :timestamp";
$params = [
    'userid' => $userid,
    'courseid' => $courseid,
    'token' => $secureToken,
    'timestamp' => $timestamp
];
$record = $DB->get_record_sql($sql, $params);

// Debugging information
echo "SQL Query: $sql<br>";
echo "Parameters: <br>";
echo "UserID: '$userid'<br>";
echo "CourseID: '$courseid'<br>";
echo "Token: '$secureToken'<br>";
echo "Timestamp: '$timestamp'<br>";

// Log the query
$log_message = "SQL Query: $sql\nParameters: " . print_r($params, true) . "\n";
file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $log_message, FILE_APPEND);

if ($record) {
    echo 'QR Code is valid and has been used.<br>';
    echo "Record from DB:<br>";
    echo "Token: '$record->token'<br>";
    echo "Timestamp: '$record->timestamp'<br>";
    // Compare the raw values directly
    echo "Raw Token Comparison: '" . strcmp($secureToken, $record->token) . "'<br>";
    echo "Raw Timestamp Comparison: '" . strcmp($timestamp, $record->timestamp) . "'<br>";

    // Log the found record
    $log_message = "Record found: " . print_r($record, true) . "\n";
    file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $log_message, FILE_APPEND);

    // Comment out the deletion for debugging purposes
    // $DB->delete_records('local_qrcompletion_tokens', ['id' => $record->id]);
    // file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', "Record deleted: " . print_r($record, true) . "\n", FILE_APPEND);
} else {
    echo 'Invalid QR Code.<br>';
    // Retrieve and display all tokens for further debugging
    $all_records = $DB->get_records('local_qrcompletion_tokens', ['userid' => $userid, 'courseid' => $courseid]);
    $log_message = "All tokens for user '$userid' in course '$courseid': " . print_r($all_records, true) . "\n";
    file_put_contents($CFG->dataroot . '/qrcompletion_logs.log', $log_message, FILE_APPEND);
    echo $log_message; // Optionally display the message for debugging
}
?>
