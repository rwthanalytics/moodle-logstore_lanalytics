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
 * Tests for the real-time log writer, in particular the "loggingmode" setting.
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics;

use logstore_lanalytics\log\store;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/fixtures/event.php');

/**
 * @covers \logstore_lanalytics\log\store
 */
final class store_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->preventResetByRollback(); // Logging waits till the transaction gets committed.
    }

    protected function enable_store(): store {
        set_config('enabled_stores', 'logstore_lanalytics', 'tool_log');
        set_config('buffersize', 0, 'logstore_lanalytics'); // Flush immediately.
        $manager = get_log_manager(true);
        $stores = $manager->get_readers();
        /** @var store $store */
        $store = $stores['logstore_lanalytics'];
        return $store;
    }

    protected function trigger_fixture_event(\stdClass $course): void {
        $context = \context_course::instance($course->id);
        $event = \logstore_lanalytics\event\unittest_executed::create([
            'context' => $context,
            'courseid' => $course->id,
        ]);
        $event->trigger();
    }

    /**
     * Default loggingmode is "realtime" - existing behaviour must be unaffected unless the
     * setting is explicitly changed.
     */
    public function test_default_loggingmode_is_realtime_and_logs_events() {
        global $DB;

        $this->assertFalse(store::is_scheduled_mode());

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->enable_store();

        $this->trigger_fixture_event($course);

        $logs = $DB->get_records('logstore_lanalytics_log');
        $this->assertCount(1, $logs);
    }

    /**
     * When loggingmode = scheduled, the real-time writer must do nothing at all.
     */
    public function test_scheduled_loggingmode_disables_realtime_writer() {
        global $DB;

        set_config('loggingmode', 'scheduled', 'logstore_lanalytics');
        $this->assertTrue(store::is_scheduled_mode());

        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $this->enable_store();

        $this->trigger_fixture_event($course);

        $logs = $DB->get_records('logstore_lanalytics_log');
        $this->assertCount(0, $logs);
    }
}
