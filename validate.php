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
 * QR code validation processing for QR Completion plugin.
 *
 * @package    local_qrcompletion
 * @copyright  2024 Randy Vermaas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$token = required_param('token', PARAM_TEXT);
$timestamp = required_param('timestamp', PARAM_INT);

// Validate the token (ensure it hasn't expired).
$currenttime = time();
if (($currenttime - $timestamp) > 300) { // Token valid for 5 minutes.
    throw new moodle_exception('qrcodeexpired', 'local_qrcompletion');
}

$record = $DB->get_record('local_qrcompletion_tokens', [
    'token' => $token,
    'timestamp' => $timestamp,
]);

if ($record) {
    echo get_string('qrcompleted', 'local_qrcompletion');
    $DB->delete_records('local_qrcompletion_tokens', ['id' => $record->id]);
} else {
    throw new moodle_exception('qrcodeinvalid', 'local_qrcompletion');
}
