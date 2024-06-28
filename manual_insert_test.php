<?php
require_once('../../config.php');

$record = new stdClass();
$record->userid = 5;
$record->courseid = 3;
$record->token = 'test_token_123';
$record->timestamp = time();

$insert_result = $DB->insert_record('local_qrcompletion_tokens', $record);
if ($insert_result) {
    echo "Manual record inserted successfully.<br>";
} else {
    echo "Manual record insertion failed.<br>";
}
?>
