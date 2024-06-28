<?php
require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$token = required_param('token', PARAM_TEXT);
$timestamp = required_param('timestamp', PARAM_INT);

// Validate the token (ensure it hasn't expired)
$current_time = time();
if (($current_time - $timestamp) > 300) { // Token valid for 5 minutes
    print_error(get_string('qrcodeexpired', 'local_qrcompletion'));
}

$record = $DB->get_record('local_qrcompletion_tokens', [
    'token' => $token,
    'timestamp' => $timestamp
]);

if ($record) {
    echo get_string('qrcompleted', 'local_qrcompletion');
    $DB->delete_records('local_qrcompletion_tokens', ['id' => $record->id]);
} else {
    print_error(get_string('qrcodeinvalid', 'local_qrcompletion'));
}
?>
