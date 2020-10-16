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
 * Upgrade for logstore_lanalytics
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

function xmldb_logstore_lanalytics_upgrade($oldversion) {
    global $DB;

	$dbman = $DB->get_manager();

    if ($oldversion < 2020101607) {
        $table = new xmldb_table('logstore_lanalytics_log');

        // Remove fields
        $fieldobjectid = new xmldb_field('objectid');
        if ($dbman->field_exists($table, $fieldobjectid)) {
            $dbman->drop_field($table, $fieldobjectid);
        }
        $fielduserid = new xmldb_field('userid');
        if ($dbman->field_exists($table, $fielduserid)) {
            $dbman->drop_field($table, $fielduserid);
        }
        $fieldos = new xmldb_field('os');
        if ($dbman->field_exists($table, $fieldos)) {
            $dbman->drop_field($table, $fieldos);
        }
        $fieldbrowser = new xmldb_field('browser');
        if ($dbman->field_exists($table, $fieldbrowser)) {
            $dbman->drop_field($table, $fieldbrowser);
        }

        // Add field
        $field = new xmldb_field('device', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, '0', 'contextid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2020101607, 'logstore', 'lanalytics');
    }

    return true;
}
