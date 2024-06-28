<?php
require_once('../../config.php');
require_once($CFG->libdir.'/completionlib.php');
require_once($CFG->dirroot.'/local/qrcompletion/qrscanlib.php'); // Including the MyQRCode class

$courseid = required_param('id', PARAM_INT);

require_login($courseid);
$context = context_course::instance($courseid);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

$PAGE->set_url('/local/qrcompletion/index.php', array('id' => $courseid));
$PAGE->set_context($context);
$PAGE->set_title(get_string('qrcompletion', 'local_qrcompletion'));
$PAGE->set_heading(get_string('qrcompletion', 'local_qrcompletion'));

$completion = new completion_info($course);

if ($completion->is_enabled()) { // Ensure completion is enabled for the course
    if (has_capability('local/qrcompletion:view', $context) && $completion->is_course_complete($USER->id)) {
        // Generate QR code with a timestamped token
        $timestamp = time();
        $token = bin2hex(random_bytes(16)); 
        $secureToken = hash('sha256', $token . $timestamp);

        // Debugging output
        echo "Generated Token: '$token'<br>";
        echo "Secure Token: '$secureToken'<br>";
        echo "Timestamp: '$timestamp'<br>";
        echo "User ID: '{$USER->id}'<br>";
        echo "Course ID: '$courseid'<br>";

        // Store the token and timestamp in the database
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->courseid = $courseid;
        $record->token = $secureToken;
        $record->timestamp = $timestamp;

        // Begin transaction
        $transaction = $DB->start_delegated_transaction();

        // Insert record and check for errors
        $insert_result = $DB->insert_record('local_qrcompletion_tokens', $record);
        if ($insert_result) {
            echo "Record inserted successfully.<br>";
            // Commit transaction
            $transaction->allow_commit();
        } else {
            echo "Record insertion failed.<br>";
            $transaction->rollback(new moodle_exception('recordinsertfail', 'local_qrcompletion', '', null, 'Failed to insert record'));
        }

        // Debugging: Retrieve and display the inserted record
        $retrieved_record = $DB->get_record('local_qrcompletion_tokens', [
            'userid' => $USER->id,
            'courseid' => $courseid,
            'token' => $secureToken,
            'timestamp' => $timestamp
        ]);
        if ($retrieved_record) {
            echo "Retrieved Record: Token: '$retrieved_record->token', Timestamp: '$retrieved_record->timestamp'<br>";
        } else {
            echo "No record found immediately after insertion.<br>";
        }

        $qrcode = new MyQRCode(); // Using the renamed class
        // Include the userid in the QR code data
        $qrcode->setText($secureToken . '|' . $timestamp . '|' . $USER->id);
        $qrcode->setOutfile($CFG->dataroot.'/qrcodes/'.$USER->id.'.png'); // Save to Moodle data directory
        $qrcode->setSize(9); // Set size to make it 3 times larger (3 * 3 = 9)
        $qrcode->generate();

        echo $OUTPUT->header();
        echo html_writer::tag('h2', get_string('qrcompletion', 'local_qrcompletion'));
        // Use serve_qrcode.php to serve the QR code image
        $imgurl = new moodle_url('/local/qrcompletion/serve_qrcode.php', array('userid' => $USER->id, 'courseid' => $courseid)); // Pass course ID
        echo html_writer::empty_tag('img', array('src' => $imgurl));
        echo $OUTPUT->footer();
    } else {
        echo $OUTPUT->header();
        echo html_writer::tag('p', get_string('coursenotcomplete', 'local_qrcompletion'));
        echo $OUTPUT->footer();
    }
} else {
    echo $OUTPUT->header();
    echo html_writer::tag('p', 'Course completion is not enabled for this course.');
    echo $OUTPUT->footer();
}
?>
