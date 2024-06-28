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
 * QR Completion plugin main script.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/local/qrcompletion/phpqrcode/qrlib.php');
require_once($CFG->dirroot . '/local/qrcompletion/qrscanlib.php');

$courseid = required_param('id', PARAM_INT);

// Ensure the user is logged in and has access to the course.
require_login($courseid);
$context = context_course::instance($courseid);

// Retrieve course record from the database.
$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

// Set up the page.
$PAGE->set_url('/local/qrcompletion/index.php', ['id' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title(get_string('qrcompletion', 'local_qrcompletion'));
$PAGE->set_heading(get_string('qrcompletion', 'local_qrcompletion'));

$completion = new completion_info($course);

// Output the header.
echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('qrcompletion', 'local_qrcompletion'));

// Check if course completion is enabled.
if ($completion->is_enabled()) {
    if (has_capability('local/qrcompletion:view', $context) && $completion->is_course_complete($USER->id)) {
        // Fetch and display the cat image.
        $imagename = get_config('local_qrcompletion', 'icon');
        $fs = get_file_storage();
        $file = $fs->get_file(context_system::instance()->id, 'local_qrcompletion', 'icon', 0, '/', $imagename);

        $iconhtml = '';
        if ($file) {
            $imageurl = moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            $iconhtml = html_writer::empty_tag('img', [
                'src' => $imageurl,
                'class' => 'spinner',
                'style' => 'width: 96px; height: 96px; animation: spin 6s cubic-bezier(0.28, -0.55, 0.28, -0.55) infinite;',
            ]);

        }

        // CSS for animation and positioning.
        echo '
        <style>
            @keyframes spin {
                0% { transform: rotate(0deg); }
                50% { transform: rotate(360deg); }
                100% { transform: rotate(0deg); }
            }
            .qr-code-container {
                position: relative;
                display: flex;
                justify-content: center;
                align-items: center;
                width: 400px;
                height: 400px;
                margin: 0 auto; /* Center the container horizontally */
            }
            .qr-code-container img.qr-code {
                display: block;
                width: 100%;
                height: 100%;
            }
            .qr-code-container .spinner {
                position: absolute;
                width: 96px;
                height: 96px;
                animation: spin 6s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
            }
            @media (max-width: 600px) {
                .qr-code-container {
                width: 92%;
                height: 92%;
                padding: 10px;
    }
}
        </style>';

        // Generate a time-sensitive token.
        $timestamp = time();
        $secret = 'your-secret-key'; // Use a secure secret key.
        $token = hash_hmac('sha256', $USER->id . '|' . $courseid . '|' . $timestamp, $secret);

        // Store the token and timestamp in the database.
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->courseid = $courseid;
        $record->token = $token;
        $record->timestamp = $timestamp;

        $transaction = $DB->start_delegated_transaction();
        $insertresult = $DB->insert_record('local_qrcompletion_tokens', $record);
        if ($insertresult) {
            $transaction->allow_commit();
        } else {
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

        // Display the QR code and spinner.
        echo '<div class="qr-code-container">';
        $imgurl = new moodle_url('/local/qrcompletion/serve_qrcode.php', [
            'userid' => $USER->id,
            'courseid' => $courseid,
        ]);
        echo html_writer::empty_tag('img', ['src' => $imgurl, 'class' => 'qr-code', 'id' => 'qrcode']);

        // Display the icon animation or fallback spinner.
        if ($iconhtml) {
            echo $iconhtml;
        } else {
            echo '<div class="spinner" style="border: 4px solid rgba(0, 0, 0, 0.1);
                border-left: 4px solid #000; border-radius: 50%;"></div>';
        }


        echo '</div>';
    } else {
        // Display message if course is not complete.
        echo html_writer::tag('p', get_string('coursenotcomplete', 'local_qrcompletion'));
    }
} else {
    // Display message if course completion is not enabled.
    echo html_writer::tag('p', 'Course completion is not enabled for this course.');
}

// Output the footer.
echo $OUTPUT->footer();
