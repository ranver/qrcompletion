<?php
require_once('../../config.php');

$userid = required_param('userid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT); // Add course ID parameter

$context = context_course::instance($courseid); // Check in course context
require_capability('local/qrcompletion:view', $context);

$filepath = $CFG->dataroot . '/qrcodes/' . $userid . '.png';

// Debugging output
if (!file_exists($filepath)) {
    header("HTTP/1.0 404 Not Found");
    echo "File not found: " . $filepath;
    die();
}

header('Content-Type: image/png');
readfile($filepath);
exit;
?>
