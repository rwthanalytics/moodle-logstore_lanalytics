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
use \core\event\base as event_base;
use logstore_lanalytics\devices;
use stdClass;

class store implements \tool_log\log\writer {
    use helper_store;
    use helper_reader;
    use \tool_log\helper\buffered_writer; // we are overwriting write(), see below

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

    public function write(\core\event\base $event) {
        // copied mostly from "tool_log\helper\buffered_writer" with some modifications
        global $PAGE;

        if ($this->is_event_ignored($event)) {
            return;
        }

        $entry = $event->get_data();
        $entry['os'] = devices::get_os();
        $entry['browser'] = devices::get_browser();

        $this->buffer[] = $entry;
        $this->count++;

        if (!isset($this->buffersize)) {
            $this->buffersize = $this->get_config('buffersize', 50);
        }
        if ($this->count >= $this->buffersize) {
            $this->flush();
        }
    }

    protected function insert_event_entries(array $events) {
        global $DB, $CFG;
        
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
            $record->os = $event['os'];
            $record->browser = $event['browser'];
            $records[] = $record;
        }

        $DB->insert_records('logstore_lanalytics_log', $records);

        // Iterate over lalog plugins and call their logger::log function
        $pluginman = \core_plugin_manager::instance();
        $lalogplugins = $pluginman->get_present_plugins('lalog');
        foreach ($lalogplugins as $plugin) {
            $path = substr($plugin->component, 6);
            include_once($CFG->dirroot. "/local/learning_analytics/logs/{$path}/classes/lalog/logger.php");
            $loggerClass = "{$plugin->component}\\logger";
            $loggerClass::log($records);
        }
    }
}
