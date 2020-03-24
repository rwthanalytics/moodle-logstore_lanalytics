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
 * Strings for logstore_lanalytics
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Learning Analytics Log';

/*
setting_course_ids
setting_course_ids_descr
setting_nontracking_roles
setting_nontracking_roles_descr
buffersize
*/

// Settings
$string['setting_course_ids'] = 'course_ids';
$string['setting_course_ids_descr'] = 'Only track the courses with the given IDs. The order of the IDs does not matter. IDs should be separated by a single comma. By default, the plugin tracks alls courses. Example: <code>10,80,10</code>.';
$string['setting_nontracking_roles'] = 'nontracking_roles';
$string['setting_nontracking_roles_descr'] = 'Define which roles should <strong>not</strong> be tracked. This is useful if you don\'t want to track specific roles (like managers or teachers). Specify the role by using the "shortname" (can be found via <em>Site Administration</em> -> <em>Users</em> tab -> <em>Permissions</em> category -> <em>Define roles</em>). By default, all roles are tracked. Example: <code>teacher,editingteacher,manager</code>';
$string['buffersize'] = 'buffersize';