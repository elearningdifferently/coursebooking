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
 * Admin settings for the Course Booking plugin.
 *
 * @package    local_coursebooking
 * @copyright  2026 Wellingtone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_coursebooking', get_string('pluginname', 'local_coursebooking'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configcheckbox(
        'local_coursebooking/enabled',
        get_string('setting_enabled', 'local_coursebooking'),
        get_string('setting_enabled_desc', 'local_coursebooking'),
        1
    ));

    // Role used when enrolling delegates.
    $roles = role_fix_names(get_all_roles(), context_system::instance(), ROLENAME_ORIGINAL);
    $roleoptions = [];
    foreach ($roles as $role) {
        $roleoptions[$role->id] = $role->localname;
    }
    $studentroles = get_archetype_roles('student');
    $defaultrole = $studentroles ? (int) reset($studentroles)->id : 0;

    $settings->add(new admin_setting_configselect(
        'local_coursebooking/enrolrole',
        get_string('setting_enrolrole', 'local_coursebooking'),
        get_string('setting_enrolrole_desc', 'local_coursebooking'),
        $defaultrole,
        $roleoptions
    ));

    $settings->add(new admin_setting_configtext(
        'local_coursebooking/maxgroupsize',
        get_string('setting_maxgroupsize', 'local_coursebooking'),
        get_string('setting_maxgroupsize_desc', 'local_coursebooking'),
        20,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_coursebooking/currencysymbol',
        get_string('setting_currency', 'local_coursebooking'),
        get_string('setting_currency_desc', 'local_coursebooking'),
        '£',
        PARAM_TEXT,
        6
    ));

    $settings->add(new admin_setting_confightmleditor(
        'local_coursebooking/intro',
        get_string('setting_intro', 'local_coursebooking'),
        get_string('setting_intro_desc', 'local_coursebooking'),
        get_string('catalogueintro', 'local_coursebooking')
    ));
}
