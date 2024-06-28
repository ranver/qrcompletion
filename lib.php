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
 * Library file for the QR Completion plugin.
 *
 * @package   local_qrcompletion
 * @copyright 2024 Randy Vermaas
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extends the course navigation for the QR Completion plugin.
 *
 * @param navigation_node $navigation The navigation node to extend.
 * @param stdClass $course The course object.
 * @param context_course $context The context object.
 */
function local_qrcompletion_extend_navigation_course($navigation, $course, $context) {
    // Debugging: Log function call.
    mtrace('local_qrcompletion_extend_navigation_course called');

    if (has_capability('local/qrcompletion:view', $context)) {
        // Debugging: Log capability check.
        mtrace('User has local/qrcompletion:view capability');

        $url = new moodle_url('/local/qrcompletion/index.php', ['id' => $course->id]);
        $navigation->add(get_string('qrcompletion', 'local_qrcompletion'), $url);
    } else {
        // Debugging: Log missing capability.
        mtrace('User does not have local/qrcompletion:view capability');
    }

    if (has_capability('moodle/course:manageactivities', $context)) {
        // Debugging: Log capability check.
        mtrace('User has moodle/course:manageactivities capability');

        $url = new moodle_url('/local/qrcompletion/validate_qr.php', ['courseid' => $course->id]);
        $navigation->add('Validate QR Code', $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/valid', ''));
    } else {
        // Debugging: Log missing capability.
        mtrace('User does not have moodle/course:manageactivities capability');
    }
}

/**
 * Serves the files for the QR Completion plugin.
 *
 * @param stdClass $course The course object.
 * @param cm_info $cm The course module info object.
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args The file arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return bool Returns false if file not found or not accessible.
 */
function local_qrcompletion_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $CFG, $DB;
    require_login();

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    if ($filearea !== 'icon') {
        return false;
    }

    $itemid = array_shift($args);
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_qrcompletion/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
