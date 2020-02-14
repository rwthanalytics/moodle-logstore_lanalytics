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

    $settings->add(new admin_setting_configcheckbox(
        'logstore_lanalytics/only_track_specified_courses',
        new lang_string('setting_only_track_specified_courses', 'logstore_lanalytics'),
        new lang_string('setting_only_track_specified_courses_descr', 'logstore_lanalytics'),
        0
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_lanalytics/course_ids',
        get_string('setting_course_ids', 'logstore_lanalytics'),
        get_string('setting_course_ids_descr', 'logstore_lanalytics'),
        '', // default value
        PARAM_RAW
    ));

    $settings->add(new admin_setting_configtext(
        'logstore_lanalytics/user_ids',
        get_string('setting_user_ids', 'logstore_lanalytics'),
        get_string('setting_user_ids_descr', 'logstore_lanalytics'),
        '', // default value
        PARAM_RAW
    ));
}
