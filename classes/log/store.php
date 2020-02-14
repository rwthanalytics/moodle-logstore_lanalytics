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
 * logstore_lanalytics
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics\log;

defined('MOODLE_INTERNAL') || die();

use \tool_log\log\manager as log_manager;
use \tool_log\helper\store as helper_store;
use \tool_log\helper\reader as helper_reader;
use \tool_log\helper\buffered_writer as helper_writer;
use \core\event\base as event_base;
use stdClass;

class store implements \tool_log\log\writer {
    use helper_store;
    use helper_reader;
    use helper_writer;

    public function __construct(log_manager $manager) {
        $this->helper_setup($manager);
    }

    protected function is_event_ignored(event_base $event) {
        if ((!CLI_SCRIPT || PHPUNIT_TEST)) {
            // Always log inside CLI scripts because we do not login there.
            if (!isloggedin() || isguestuser()) {
                return true;
            }
        }

        return false;
    }

    protected function insert_event_entries(array $events) {
        global $DB;
        $records = [];
        foreach ($events as $event) {
            $eventid = 0;
            $dbevent = $DB->get_record('logstore_lanalytics_evtname', ['eventname' => $event['eventname']], 'id');
            if ($dbevent) {
                $eventid = $dbevent->id;
            } else {
                $evtrecord = new stdClass();
                $evtrecord->eventname = $event['eventname'];
                $eventid = $DB->insert_record('logstore_lanalytics_evtname', $evtrecord, true);
            }
            $record = new stdClass();
            $record->eventid = $eventid;
            $record->timecreated = $event['timecreated'];
            $record->courseid = $event['courseid'];
            $record->objectid = $event['objectid'];
            $record->userid = $event['userid'];
            $record->device = 0;
            $records[] = $record;
        }

        $DB->insert_records('logstore_lanalytics_log', $records);
    }

}
