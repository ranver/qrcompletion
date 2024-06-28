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
 * QR Code validation page for QR Completion plugin.
 *
 * @package    local_qrcompletion
 * @copyright  2024 Randy Vermaas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url('/local/qrcompletion/validate_qr.php', ['courseid' => $courseid]);
$PAGE->set_context($context);
$PAGE->set_title('QR Code Validation');
$PAGE->set_heading('QR Code Validation');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Scan and Validate QR Code');

// Ensure the correct path to the html5-qrcode library.
echo html_writer::script('', $CFG->wwwroot . '/local/qrcompletion/html5-qrcode/html5-qrcode.min.js');

// Hidden field to store the course ID
echo html_writer::empty_tag('input', ['type' => 'hidden', 'id' => 'course-id', 'value' => $courseid]);

// HTML5 QR Code Scanner.
echo html_writer::start_tag('div', ['id' => 'qr-reader', 'class' => 'qr-scanner']);
echo html_writer::end_tag('div');
echo html_writer::tag('div', '', ['id' => 'qr-reader-results']);
echo html_writer::empty_tag('div', ['id' => 'qr-result-image']);

// Closing the script tag for the previous JavaScript.
echo html_writer::end_tag('script');

// Output the footer.
echo $OUTPUT->footer();
?>

<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    const courseId = document.getElementById('course-id').value;
    const processQrUrl = M.cfg.wwwroot + '/local/qrcompletion/process_qr.php';

    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`);
        // Send the scanned QR code to the server for validation.
        const xhr = new XMLHttpRequest();
        xhr.open('POST', processQrUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('qr-reader-results').innerHTML = xhr.responseText;
                console.log('Server response:', xhr.responseText); // Debugging message
            } else {
                document.getElementById('qr-reader-results').innerHTML = 
                    'An error occurred while validating the QR code. Status: ' + xhr.status + ', Response: ' + xhr.responseText;
                console.log('Validation error:', xhr.status, xhr.responseText); // Debugging message
            }
        };
        xhr.send('qrcode=' + encodeURIComponent(decodedText) + '&courseid=' + encodeURIComponent(courseId));
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        'qr-reader', {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            rememberLastUsedCamera: true,
            supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
        }
    );
    html5QrcodeScanner.render(onScanSuccess);

    // Remove the information icon immediately.
    const infoIcon = document.querySelector('img[alt="Info icon"]');
    if (infoIcon) {
        infoIcon.style.display = 'none';
    }
});
</script>

<style>
/* Ensure this CSS does not interfere with the QR code scanner */
body {
    font-family: Arial, sans-serif;
    background-color: #f9f9f9;
}

.qr-scanner {
    width: 100%;
    max-width: 500px;
    margin: 0 auto;
    text-align: center;
    border: 1px solid #ccc;
    padding: 20px;
    box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
}

#qr-reader {
    width: 100%;
}

#qr-result-image {
    margin-top: 10px;
    text-align: center;
}

@media (max-width: 600px) {
    .qr-scanner {
        width: 80%;
        padding: 10px;
    }
}

/* Hide the info icon */
img[alt="Info icon"] {
    display: none !important;
}
</style>
