<?php
require_once('../../config.php');

$courseid = required_param('courseid', PARAM_INT);
$context = context_course::instance($courseid);

require_login($courseid);
require_capability('moodle/course:manageactivities', $context);

$PAGE->set_url('/local/qrcompletion/validate_qr.php', array('courseid' => $courseid));
$PAGE->set_context($context);
$PAGE->set_title('QR Code Validation');
$PAGE->set_heading('QR Code Validation');

echo $OUTPUT->header();
echo html_writer::tag('h2', 'Scan and Validate QR Code');

// Ensure the correct path to the html5-qrcode library
echo html_writer::script('', $CFG->wwwroot . '/local/qrcompletion/html5-qrcode/html5-qrcode.min.js');

// HTML5 QR Code Scanner
echo html_writer::start_tag('div', array('id' => 'qr-reader', 'style' => 'width:500px'));
echo html_writer::end_tag('div');
echo html_writer::tag('div', '', array('id' => 'qr-reader-results'));

// JavaScript to handle QR code scanning
echo html_writer::start_tag('script', array('type' => 'text/javascript'));
?>
document.addEventListener('DOMContentLoaded', function() {
    function onScanSuccess(decodedText, decodedResult) {
        console.log(`Scan result: ${decodedText}`);
        // Send the scanned QR code to the server for validation
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '<?php echo new moodle_url("/local/qrcompletion/process_qr.php"); ?>', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                document.getElementById('qr-reader-results').innerHTML = xhr.responseText;
            } else {
                document.getElementById('qr-reader-results').innerHTML = 'An error occurred while validating the QR code.';
            }
        };
        xhr.send('qrcode=' + encodeURIComponent(decodedText) + '&courseid=<?php echo $courseid; ?>');
    }

    let html5QrcodeScanner = new Html5QrcodeScanner(
        'qr-reader', { fps: 10, qrbox: 250 });
    html5QrcodeScanner.render(onScanSuccess);
});
<?php
echo html_writer::end_tag('script');

echo $OUTPUT->footer();
?>
