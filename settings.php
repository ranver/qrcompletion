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
 * Settings for local_qrcompletion.
 *
 * @package    local_qrcompletion
 * @copyright  2024 Randy Vermaas
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_qrcompletion', get_string('pluginname', 'local_qrcompletion'));

    // Icon file upload setting.
    $settings->add(new admin_setting_configstoredfile('local_qrcompletion/icon',
        get_string('icon', 'local_qrcompletion'),
        get_string('icon_desc', 'local_qrcompletion'),
        'icon'));

    $ADMIN->add('localplugins', $settings);
}
