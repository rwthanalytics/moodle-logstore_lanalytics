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
 * Tests for the scheduled import task (\logstore_lanalytics\task\import_task).
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics;

use logstore_lanalytics\task\import_task;
use logstore_lanalytics\log\filter;
use ReflectionMethod;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \logstore_lanalytics\task\import_task
 */
final class import_task_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        filter::reset_cache();

        // Sensible defaults for filtering, so tests are unaffected unless overridden.
        set_config('log_scope', 'all', 'logstore_lanalytics');
        set_config('course_ids', '', 'logstore_lanalytics');
        set_config('tracking_roles', '', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        // The task warns (but still runs) if this isn't enabled - keep it quiet/realistic.
        set_config('enabled_stores', 'logstore_standard', 'tool_log');
    }

    /**
     * Inserts a raw row directly into {logstore_standard_log}, bypassing the event system so
     * tests have full, deterministic control over courseid/contextid/timecreated/eventname.
     */
    protected function create_standard_log_row(array $overrides = []): int {
        global $DB;

        $record = (object) array_merge([
            'eventname' => '\\logstore_lanalytics\\event\\unittest_executed',
            'component' => 'logstore_lanalytics',
            'action' => 'viewed',
            'target' => 'course',
            'crud' => 'r',
            'edulevel' => 2,
            'contextid' => 1,
            'contextlevel' => 50,
            'contextinstanceid' => 0,
            'userid' => 0,
            'courseid' => 0,
            'anonymous' => 0,
            'timecreated' => time(),
        ], $overrides);

        return (int) $DB->insert_record('logstore_standard_log', $record);
    }

    /**
     * Invokes a protected/private static method via reflection.
     */
    protected function call_static(string $method, array $args = []) {
        $ref = new ReflectionMethod(import_task::class, $method);
        $ref->setAccessible(true);
        return $ref->invokeArgs(null, $args);
    }

    public function test_execute_is_noop_when_not_scheduled() {
        global $DB;

        set_config('loggingmode', 'realtime', 'logstore_lanalytics');
        $this->create_standard_log_row(['courseid' => 5, 'timecreated' => time()]);

        $task = new import_task();
        $task->execute();

        $this->assertCount(0, $DB->get_records('logstore_lanalytics_log'));
        $this->assertFalse(get_config('logstore_lanalytics', 'lastimportedid'));
    }

    public function test_execute_imports_rows_and_advances_checkpoint() {
        global $DB;

        set_config('loggingmode', 'scheduled', 'logstore_lanalytics');

        $id1 = $this->create_standard_log_row(['courseid' => 5, 'contextid' => 100, 'timecreated' => 1000]);
        $id2 = $this->create_standard_log_row(['courseid' => 6, 'contextid' => 101, 'timecreated' => 1001]);

        $task = new import_task();
        $task->execute();

        $logs = $DB->get_records('logstore_lanalytics_log', [], 'id ASC');
        $this->assertCount(2, $logs);

        $checkpoint = get_config('logstore_lanalytics', 'lastimportedid');
        $this->assertEquals($id2, $checkpoint);
    }

    public function test_execute_respects_log_scope_filter() {
        global $DB;

        set_config('loggingmode', 'scheduled', 'logstore_lanalytics');
        set_config('log_scope', 'include', 'logstore_lanalytics');
        set_config('course_ids', '5', 'logstore_lanalytics');

        $this->create_standard_log_row(['courseid' => 5, 'contextid' => 100, 'timecreated' => 1000]);
        $this->create_standard_log_row(['courseid' => 6, 'contextid' => 101, 'timecreated' => 1001]);

        $task = new import_task();
        $task->execute();

        $logs = $DB->get_records('logstore_lanalytics_log');
        $this->assertCount(1, $logs);
        $this->assertEquals(5, reset($logs)->courseid);
    }

    public function test_execute_resumes_from_checkpoint_without_duplicating() {
        global $DB;

        set_config('loggingmode', 'scheduled', 'logstore_lanalytics');

        $this->create_standard_log_row(['courseid' => 5, 'contextid' => 100, 'timecreated' => 1000]);

        $task = new import_task();
        $task->execute();
        $this->assertCount(1, $DB->get_records('logstore_lanalytics_log'));

        // Running again immediately with no new rows must not duplicate anything.
        $task->execute();
        $this->assertCount(1, $DB->get_records('logstore_lanalytics_log'));

        // A newly added row must be picked up on the next run, and only that one.
        $this->create_standard_log_row(['courseid' => 5, 'contextid' => 102, 'timecreated' => 2000]);
        $task->execute();
        $this->assertCount(2, $DB->get_records('logstore_lanalytics_log'));
    }

    public function test_derive_starting_point_matches_via_contextid() {
        global $DB;

        // A row already processed in the past (e.g. via real-time logging).
        $evtname = '\\logstore_lanalytics\\event\\unittest_executed';
        $eventid = filter::resolve_eventid($evtname);

        $lastlog = (object) [
            'eventid' => $eventid,
            'timecreated' => 5000,
            'courseid' => 42,
            'contextid' => 999,
            'device' => 0,
        ];
        $DB->insert_record('logstore_lanalytics_log', $lastlog);

        // Older + newer matching candidate rows in logstore_standard_log; the derived checkpoint
        // must be the matching row's id, not simply the newest row overall.
        $this->create_standard_log_row(['courseid' => 42, 'contextid' => 999, 'timecreated' => 5000, 'eventname' => $evtname]);
        $expectedid = $this->create_standard_log_row(['courseid' => 42, 'contextid' => 999, 'timecreated' => 5000, 'eventname' => $evtname]);
        $this->create_standard_log_row(['courseid' => 42, 'contextid' => 999, 'timecreated' => 6000, 'eventname' => $evtname]);

        $derived = $this->call_static('derive_starting_point');
        $this->assertEquals($expectedid, $derived);
    }

    public function test_derive_starting_point_falls_back_to_eventname_when_contextid_zero() {
        global $DB;

        $evtname = '\\logstore_lanalytics\\event\\unittest_executed';
        $eventid = filter::resolve_eventid($evtname);

        // contextid = 0 is a known edge case that cannot be trusted to match reliably.
        $lastlog = (object) [
            'eventid' => $eventid,
            'timecreated' => 7000,
            'courseid' => 42,
            'contextid' => 0,
            'device' => 0,
        ];
        $DB->insert_record('logstore_lanalytics_log', $lastlog);

        // Standard log row has some other (real) contextid, but matches on eventname/courseid/timecreated.
        $expectedid = $this->create_standard_log_row([
            'courseid' => 42, 'contextid' => 555, 'timecreated' => 7000, 'eventname' => $evtname,
        ]);

        $derived = $this->call_static('derive_starting_point');
        $this->assertEquals($expectedid, $derived);
    }

    public function test_derive_starting_point_returns_zero_when_no_local_data() {
        $derived = $this->call_static('derive_starting_point');
        $this->assertEquals(0, $derived);
    }

    public function test_execute_derives_checkpoint_on_first_run_after_switching_modes() {
        global $DB;

        // Simulate real-time logging having already produced a row previously.
        $evtname = '\\logstore_lanalytics\\event\\unittest_executed';
        $eventid = filter::resolve_eventid($evtname);
        $DB->insert_record('logstore_lanalytics_log', (object) [
            'eventid' => $eventid, 'timecreated' => 1000, 'courseid' => 5, 'contextid' => 100, 'device' => 0,
        ]);
        $matchedid = $this->create_standard_log_row(['courseid' => 5, 'contextid' => 100, 'timecreated' => 1000, 'eventname' => $evtname]);
        // A new row that arrived after the switch to scheduled mode - only this one should import.
        $newid = $this->create_standard_log_row(['courseid' => 5, 'contextid' => 101, 'timecreated' => 2000, 'eventname' => $evtname]);

        set_config('loggingmode', 'scheduled', 'logstore_lanalytics');
        $this->assertFalse(get_config('logstore_lanalytics', 'lastimportedid'));

        $task = new import_task();
        $task->execute();

        // Only the new row (not already covered by real-time logging) should have been imported.
        $logs = $DB->get_records('logstore_lanalytics_log', [], 'id ASC');
        $this->assertCount(2, $logs); // The pre-existing row + the newly imported one.
        $this->assertEquals($newid, get_config('logstore_lanalytics', 'lastimportedid'));
    }
}
