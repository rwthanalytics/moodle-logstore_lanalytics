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
use \context_course;

const MOODLE_API = 10100;

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
            if (!isloggedin()) {
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

    protected function insert_event_entries(array $events) {
        global $DB, $CFG;

        $courseids = [];
        $logscope = get_config('logstore_lanalytics', 'log_scope'); // 'all', 'include', 'exclude'
        if ($logscope === false) {
            $logscope = 'all';
        }

        if ($logscope === 'include' || $logscope === 'exclude') {
            $courseids = get_config('logstore_lanalytics', 'course_ids');
            if ($courseids === false || $courseids === '') {
                $courseids = [];
            } else {
                $courseids = array_map('trim', explode(',', $courseids));
            }
        }

        $trackingrolesstr = get_config('logstore_lanalytics', 'tracking_roles');
        $trackingroles = [];
        if ($trackingrolesstr !== false && $trackingrolesstr !== '') {
            $trackingroles = array_map('trim', explode(',', $trackingrolesstr));
        }

        $nottrackingrolesstr = get_config('logstore_lanalytics', 'nontracking_roles');
        $nottrackingroles = [];
        if ($nottrackingrolesstr !== false && $nottrackingrolesstr !== '') {
            $nottrackingroles = array_map('trim', explode(',', $nottrackingrolesstr));
        }

        $records = [];
        $eventsToTrack = [];
        foreach ($events as $event) {
            if ($logscope !== 'all' // first checking the fast option
                && (($logscope === 'include' && !in_array($event['courseid'], $courseids))
                || ($logscope === 'exclude' && in_array($event['courseid'], $courseids)))) {
                continue;
            }
            if (count($trackingroles) !== 0 || count($nottrackingroles) !== 0) {
                $coursecontext = context_course::instance($event['courseid'], IGNORE_MISSING);
                $trackevent = true;
                if ($coursecontext) { // context might not be defined for global events like login, main page.
                    $userroles = get_user_roles($coursecontext, $event['userid']);
                    if (isguestuser()) {
                        // we "fake" a guest role here as only the shortname matters (that way, we don't need another database request)
                        $guestrole = new \stdClass;
                        $guestrole->shortname = 'guest';
                        $userroles[] = $guestrole;
                    }
                    if (count($trackingroles) !== 0) { // whitelist mode, respecting blacklist
                        $trackevent = false;
                        foreach ($userroles as $role) {
                            if (in_array($role->shortname, $nottrackingroles)) { // blacklisted
                                $trackevent = false;
                                break;
                            }
                            if (in_array($role->shortname, $trackingroles)) { // whitelisted
                                $trackevent = true;
                            }
                        }
                    } else { // blacklist mode, no whitelist defined
                        foreach ($userroles as $role) {
                            if (in_array($role->shortname, $nottrackingroles)) {
                                $trackevent = false;
                                break;
                            }
                        }
                    }
                } else if (count($trackingroles) !== 0) {
                    // whitelist is active -> only track specific roles, therefore skip this one as no role is defined
                    $trackevent = false;
                }
                if (!$trackevent) {
                    continue;
                }
            }

            $eventsToTrack[] = $event;
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
