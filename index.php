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

// Include CSS and JS files.
$PAGE->requires->css('/local/qrcompletion/css/qrcompletion.css');
$PAGE->requires->js('/local/qrcompletion/js/qrcompletion.js');

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
            echo '<div class="spinner"></div>';
        }

        echo '</div>';

        // Modal structure.
        echo html_writer::start_tag('div', ['id' => 'qr-modal', 'class' => 'modal']);
        echo html_writer::start_tag('div', ['class' => 'modal-content']);
        echo html_writer::start_tag('span', ['class' => 'close']);
        echo '&times;';
        echo html_writer::end_tag('span');
        echo html_writer::tag('div', '', ['id' => 'modal-content']);
        echo html_writer::end_tag('div');
        echo html_writer::end_tag('div');

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
