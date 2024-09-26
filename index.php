<?php
// This file is part of Moodle - http://moodle.org/
//
// ... [License and package comments] ...

/**
 * QR Completion plugin main script.
 *
 * @package   local_qrcompletion
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include Moodle's configuration and necessary libraries.
require_once('../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/qrcompletion/phpqrcode/qrlib.php');

// Get the course ID from URL parameters.
$courseid = required_param('id', PARAM_INT);

// Ensure the user is logged in and has access to the course.
require_login($courseid);
$context = context_course::instance($courseid);

// Retrieve the course record from the database.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/local/qrcompletion/index.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('qrcompletion', 'local_qrcompletion'));
$PAGE->set_heading(get_string('qrcompletion', 'local_qrcompletion'));

// Include CSS file.
$PAGE->requires->css('/local/qrcompletion/css/qrcompletion.css');

// Retrieve plugin settings.
$enableanimation = get_config('local_qrcompletion', 'enableanimation');
$animationpreset = get_config('local_qrcompletion', 'animationpreset');

// Create a completion info instance for the course.
$completion = new completion_info($course);

// Output the header.
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('qrcompletion', 'local_qrcompletion'));

// Check if course completion is enabled.
if ($completion->is_enabled()) {
    // Check if the user has the capability and if the course is complete for the user.
    if (has_capability('local/qrcompletion:view', $context) && $completion->is_course_complete($USER->id)) {
        // Fetch and display the icon image.
        $imagename = get_config('local_qrcompletion', 'icon');
        $fs = get_file_storage();
        $file = $fs->get_file(context_system::instance()->id, 'local_qrcompletion', 'icon', 0, '/', $imagename);

        $iconhtml = '';
        if ($file) {
            // Generate the URL for the icon image.
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );

            // Initialize animation class.
            $animationclass = ''; // Default to no animation.

            // Determine the CSS classes based on settings.
            if ($enableanimation) {
                switch ($animationpreset) {
                    case 'spin_fast':
                        $animationclass = 'spin-fast';
                        break;
                    case 'spin_back_and_forth':
                        $animationclass = 'spin-back-and-forth';
                        break;
                    case 'spin_accelerate_decelerate':
                        $animationclass = 'spin-accelerate-decelerate';
                        break;
                    case 'spin_slow':
                        $animationclass = 'spin-slow';
                        break;
                    case 'none':
                    default:
                        $animationclass = ''; // No animation.
                        break;
                }
            }

            // Combine classes.
            $iconclasses = 'icon-over-qr';
            if ($animationclass != '') {
                $iconclasses .= ' ' . $animationclass;
            }

            // Create the HTML for the icon image.
            $iconhtml = html_writer::empty_tag('img', [
                'src' => $imageurl,
                'class' => $iconclasses,
            ]);
        }

        // Generate a time-sensitive token.
        $timestamp = time();
        $secret = 'uQjy-yQJG-4LGY'; // Replace with a secure secret key.
        $token = hash_hmac('sha256', $USER->id . '|' . $courseid . '|' . $timestamp, $secret);

        // Store the token and timestamp in the database.
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->courseid = $courseid;
        $record->token = $token;
        $record->timestamp = $timestamp;

        // Start a transaction for database operations.
        $transaction = $DB->start_delegated_transaction();
        $insertresult = $DB->insert_record('local_qrcompletion_tokens', $record);
        if ($insertresult) {
            $transaction->allow_commit();
        } else {
            // Roll back the transaction if insert fails.
            $transaction->rollback(
                new moodle_exception(
                    'recordinsertfail',
                    'local_qrcompletion',
                    '',
                    null,
                    'Failed to insert record'
                )
            );
        }

        // Generate the QR code.
        $qrcontent = $token . '|' . $timestamp . '|' . $USER->id;
        $qrcodepath = $CFG->dataroot . '/qrcodes/' . $USER->id . '.png';
        QRcode::png($qrcontent, $qrcodepath, QR_ECLEVEL_H, 9); // Use high error correction level (H).

        // Display the QR code and icon.
        echo '<div class="qr-code-container">';

        // Generate the URL for the QR code image.
        $imgurl = new moodle_url('/local/qrcompletion/serve_qrcode.php', [
            'userid' => $USER->id,
            'courseid' => $courseid,
        ]);

        // Display the QR code image.
        echo html_writer::empty_tag('img', [
            'src' => $imgurl,
            'class' => 'qr-code',
            'id' => 'qrcode',
        ]);

        // Display the icon over the QR code.
        if ($iconhtml) {
            echo $iconhtml;
        } else {
            // If no icon is uploaded, display a default spinner or message.
            echo '<div class="spinner"></div>';
        }

        echo '</div>'; // Close qr-code-container div.

    } else {
        // Display message if course is not complete.
        echo html_writer::tag('p', get_string('coursenotcomplete', 'local_qrcompletion'));
    }
} else {
    // Display message if course completion is not enabled.
    echo html_writer::tag('p', get_string('coursecompletionnotenabled', 'local_qrcompletion'));
}

// Output the footer.
echo $OUTPUT->footer();
