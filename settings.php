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
 * logstore_lanalytics settings
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings->add(new admin_setting_configselect(
        'logstore_lanalytics/log_scope',
        get_string('setting_log_scope', 'logstore_lanalytics'),
        get_string('setting_log_scope_descr', 'logstore_lanalytics'),
        'all', // default value
        [
            'all' => get_string('setting_log_scope_all', 'logstore_lanalytics'),
            'include' => get_string('setting_log_scope_include', 'logstore_lanalytics'),
            'exclude' => get_string('setting_log_scope_exclude', 'logstore_lanalytics')
        ]
    ));

    // This is only a textarea to make it more comforable entering the values
    $settings->add(new admin_setting_configtextarea(
        'logstore_lanalytics/course_ids',
        get_string('setting_course_ids', 'logstore_lanalytics'),
        get_string('setting_course_ids_descr', 'logstore_lanalytics'),
        '',
        PARAM_RAW,
        '60',
        '2'
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_lanalytics/tracking_roles',
        get_string('setting_tracking_roles', 'logstore_lanalytics'),
        get_string('setting_tracking_roles_descr', 'logstore_lanalytics'),
        '', // default value
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_lanalytics/nontracking_roles',
        get_string('setting_nontracking_roles', 'logstore_lanalytics'),
        get_string('setting_nontracking_roles_descr', 'logstore_lanalytics'),
        '', // default value
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_lanalytics/buffersize',
        get_string('buffersize', 'logstore_lanalytics'),
        '',
        '50',
        PARAM_INT
    ));
}
