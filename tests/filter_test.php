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
 * Tests for the shared filtering logic used by both the real-time writer and the
 * scheduled import task.
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics;

use logstore_lanalytics\log\filter;

defined('MOODLE_INTERNAL') || die();

/**
 * @covers \logstore_lanalytics\log\filter
 */
final class filter_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        filter::reset_cache();
    }

    public function test_log_scope_all_tracks_every_course() {
        set_config('log_scope', 'all', 'logstore_lanalytics');
        set_config('course_ids', '', 'logstore_lanalytics');
        set_config('tracking_roles', '', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        $user = $this->getDataGenerator()->create_user();

        $this->assertTrue(filter::should_track_event(123, $user->id));
        $this->assertTrue(filter::should_track_event(456, $user->id));
    }

    public function test_log_scope_include_only_tracks_listed_courses() {
        set_config('log_scope', 'include', 'logstore_lanalytics');
        set_config('course_ids', '10, 20', 'logstore_lanalytics');
        set_config('tracking_roles', '', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        $user = $this->getDataGenerator()->create_user();

        $this->assertTrue(filter::should_track_event(10, $user->id));
        $this->assertTrue(filter::should_track_event(20, $user->id));
        $this->assertFalse(filter::should_track_event(30, $user->id));
    }

    public function test_log_scope_exclude_skips_listed_courses() {
        set_config('log_scope', 'exclude', 'logstore_lanalytics');
        set_config('course_ids', '10, 20', 'logstore_lanalytics');
        set_config('tracking_roles', '', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        $user = $this->getDataGenerator()->create_user();

        $this->assertFalse(filter::should_track_event(10, $user->id));
        $this->assertTrue(filter::should_track_event(30, $user->id));
    }

    public function test_tracking_roles_whitelist() {
        set_config('log_scope', 'all', 'logstore_lanalytics');
        set_config('tracking_roles', 'student', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');

        $this->assertTrue(filter::should_track_event($course->id, $student->id));
        $this->assertFalse(filter::should_track_event($course->id, $teacher->id));
    }

    public function test_nontracking_roles_blacklist_has_priority() {
        set_config('log_scope', 'all', 'logstore_lanalytics');
        set_config('tracking_roles', 'student', 'logstore_lanalytics');
        set_config('nontracking_roles', 'student', 'logstore_lanalytics');

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');

        // Blacklisted even though also whitelisted - blacklist wins.
        $this->assertFalse(filter::should_track_event($course->id, $student->id));
    }

    public function test_missing_course_context_respects_whitelist_mode() {
        set_config('log_scope', 'all', 'logstore_lanalytics');
        set_config('tracking_roles', 'student', 'logstore_lanalytics');
        set_config('nontracking_roles', '', 'logstore_lanalytics');

        $user = $this->getDataGenerator()->create_user();

        // Courseid 0 / global events have no course context - whitelist mode should skip them.
        $this->assertFalse(filter::should_track_event(0, $user->id));
    }

    public function test_resolve_eventid_creates_and_reuses_record() {
        global $DB;

        $eventname = '\\logstore_lanalytics\\event\\unittest_executed';

        $firstid = filter::resolve_eventid($eventname);
        $this->assertIsInt($firstid);
        $this->assertEquals(1, $DB->count_records('logstore_lanalytics_evtname', ['eventname' => $eventname]));

        $secondid = filter::resolve_eventid($eventname);
        $this->assertEquals($firstid, $secondid);
        $this->assertEquals(1, $DB->count_records('logstore_lanalytics_evtname', ['eventname' => $eventname]));
    }
}
