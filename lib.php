<?php
defined('MOODLE_INTERNAL') || die();

function local_qrcompletion_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('local/qrcompletion:view', $context)) {
        $url = new moodle_url('/local/qrcompletion/index.php', array('id' => $course->id));
        $navigation->add(get_string('qrcompletion', 'local_qrcompletion'), $url);
    }

    if (has_capability('moodle/course:manageactivities', $context)) {
        $url = new moodle_url('/local/qrcompletion/validate_qr.php', array('courseid' => $course->id));
        $navigation->add('Validate QR Code', $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/valid', ''));
    }
}
?>
