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

const MOODLE_API = 10100;

class store implements \tool_log\log\writer {
    use helper_store;
    use helper_reader;
    use \tool_log\helper\buffered_writer; // we are overwriting write(), see below

    public function __construct(log_manager $manager) {
        $this->helper_setup($manager);
    }

    /**
     * Returns true if the plugin is configured to use "scheduled" logging mode, meaning
     * the real-time writer should not do any processing (see write() below).
     *
     * @return bool
     */
    public static function is_scheduled_mode(): bool {
        $loggingmode = get_config('logstore_lanalytics', 'loggingmode');
        return $loggingmode === 'scheduled';
    }

    protected function is_event_ignored(event_base $event) {
        if ((!CLI_SCRIPT || PHPUNIT_TEST)) {
            // Always log inside CLI scripts because we do not login there.
            if (!isloggedin()) {
                return true;
            }
        }

        return false;
    }

    public function write(\core\event\base $event) {
        // copied mostly from "tool_log\helper\buffered_writer" with some modifications
        global $PAGE;

        if (self::is_scheduled_mode()) {
            // In "scheduled" logging mode, all data comes from logstore_standard_log via the
            // scheduled import task instead, so we deliberately do no work here at all.
            return;
        }

        if ($this->is_event_ignored($event)) {
            return;
        }

        $entry = $event->get_data();
        // add similar data as in buffered_writer to make it more compatible
        $entry['realuserid'] = \core\session\manager::is_loggedinas() ? $GLOBALS['USER']->realuser : null;
        $entry['origin'] = $PAGE->requestorigin; // 'ws' (API/App), 'web', 'restore' or 'cli'
        $entry['ip'] = $PAGE->requestip; // could later be used by a log to track if request is from inside or outside of specific network
        if ($PAGE->requestorigin === 'ws') {
            $entry['device'] = MOODLE_API;
        } else {
            $entry['device'] = devices::get_device();
        }

        $this->buffer[] = $entry;
        $this->count++;

        if (!isset($this->buffersize)) {
            $this->buffersize = $this->get_config('buffersize', 50);
        }
        if ($this->count >= $this->buffersize) {
            $this->flush();
        }
    }

    protected function insert_event_entries($events) {
        global $DB, $CFG;

        $settings = filter::get_settings(true); // Reload once per flush to pick up setting changes.

        $records = [];
        $eventsToTrack = [];
        foreach ($events as $event) {
            if (!filter::should_track_event($event['courseid'], $event['userid'], $settings)) {
                continue;
            }

            $eventsToTrack[] = $event;
            $eventid = filter::resolve_eventid($event['eventname']);
            $record = new stdClass();
            $record->eventid = $eventid;
            $record->timecreated = $event['timecreated'];
            $record->courseid = $event['courseid'];
            $record->contextid = $event['contextid'];
            $record->device = $event['device'];
            $records[] = $record;
        }

        if (count($records) !== 0) {
            $DB->insert_records('logstore_lanalytics_log', $records);

            // Iterate over lalog plugins and call their logger::log function
            $pluginman = \core_plugin_manager::instance();
            $lalogplugins = $pluginman->get_present_plugins('lalog');
            if ($lalogplugins !== null) {
                foreach ($lalogplugins as $plugin) {
                    $path = substr($plugin->component, 6);
                    include_once($CFG->dirroot. "/local/learning_analytics/logs/{$path}/classes/lalog/logger.php");
                    $loggerClass = "{$plugin->component}\\logger";
                    $loggerClass::log($eventsToTrack);
                }
            }
        }
    }
}
