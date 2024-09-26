<?php
// This file is part of Moodle - http://moodle.org/
//
// ... [License and package comments] ...

/**
 * Settings for local_qrcompletion.
 *
 * @package    local_qrcompletion
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

    // Animation toggle setting.
    $settings->add(new admin_setting_configcheckbox('local_qrcompletion/enableanimation',
        get_string('enableanimation', 'local_qrcompletion'),
        get_string('enableanimation_desc', 'local_qrcompletion'),
        '0'));  // Default: Off (static image)

    // Preset animations dropdown.
    $settings->add(new admin_setting_configselect('local_qrcompletion/animationpreset',
        get_string('animationpreset', 'local_qrcompletion'),
        get_string('animationpreset_desc', 'local_qrcompletion'),
        'spin_slow', // Default preset
        [
            'none' => get_string('preset_none', 'local_qrcompletion'),         // No animation (still image)
            'spin_slow' => get_string('preset_spin_slow', 'local_qrcompletion'), // Slow spin (6s)
            'spin_fast' => get_string('preset_spin_fast', 'local_qrcompletion'), // Fast spin (2s)
            'spin_back_and_forth' => get_string('preset_spin_back_and_forth', 'local_qrcompletion'), // Spin back and forth
            'spin_accelerate_decelerate' => get_string('preset_spin_accelerate_decelerate', 'local_qrcompletion'), // Spin accelerate/decelerate
        ]
    ));

    $ADMIN->add('localplugins', $settings);
}
