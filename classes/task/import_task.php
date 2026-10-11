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
 * Scheduled log import task.
 *
 * Only active when the "loggingmode" setting is "scheduled". Reads events from Moodle's core
 * {logstore_standard_log} table in batches, applies the same log_scope/tracking_roles filtering
 * as the real-time writer (see classes/log/store.php and classes/log/filter.php), and inserts the
 * survivors into {logstore_lanalytics_log}. This moves the filtering/DB work off the request path
 * (e.g. during exam-time login/attempt spikes) onto a nightly background task.
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics\task;

use logstore_lanalytics\log\filter;
use stdClass;

defined('MOODLE_INTERNAL') || die();

class import_task extends \core\task\scheduled_task {

    /** @var int default batch size, used if the import_batchsize setting is missing/invalid */
    const DEFAULT_BATCHSIZE = 100000;

    /** @var int default time limit (seconds), used if the import_timelimit setting is missing/invalid */
    const DEFAULT_TIMELIMIT = 300;

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskimport', 'logstore_lanalytics');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        global $DB;

        $loggingmode = get_config('logstore_lanalytics', 'loggingmode');
        if ($loggingmode !== 'scheduled') {
            mtrace('  Logging mode is not "scheduled", nothing to import.');
            return;
        }

        if (!self::is_standard_log_store_enabled()) {
            mtrace('  WARNING: the "Standard log" store (logstore_standard) does not appear to be '.
                'enabled. Scheduled mode reads from logstore_standard_log, so no new events will be '.
                'imported until it is enabled via Site administration > Plugins > Logging > Manage log stores.');
        }

        $batchsize = (int) get_config('logstore_lanalytics', 'import_batchsize');
        if ($batchsize <= 0) {
            $batchsize = self::DEFAULT_BATCHSIZE;
        }

        $timelimit = (int) get_config('logstore_lanalytics', 'import_timelimit');
        if ($timelimit <= 0) {
            $timelimit = self::DEFAULT_TIMELIMIT;
        }

        $lastid = get_config('logstore_lanalytics', 'lastimportedid');
        if ($lastid === false || $lastid === '' || !is_numeric($lastid)) {
            $lastid = self::derive_starting_point();
            set_config('lastimportedid', $lastid, 'logstore_lanalytics');
            mtrace("  No valid checkpoint found, derived starting point: id {$lastid}.");
        } else {
            $lastid = (int) $lastid;

            // Guard against a checkpoint that can no longer be valid, e.g. because
            // logstore_standard_log was purged/rotated in the meantime.
            $maxstandardid = (int) $DB->get_field_sql("SELECT MAX(id) FROM {logstore_standard_log}");
            if ($lastid > $maxstandardid) {
                mtrace("  Stored checkpoint (id {$lastid}) is beyond the newest logstore_standard_log ".
                    "id ({$maxstandardid}), re-deriving starting point.");
                $lastid = self::derive_starting_point();
                set_config('lastimportedid', $lastid, 'logstore_lanalytics');
            }
        }

        filter::reset_cache();
        $start = time();
        $totalimported = 0;
        $startedat = $lastid;

        while (self::has_more_rows($lastid) && (time() < $start + $timelimit)) {
            $limitid = $lastid + $batchsize;
            mtrace("  Importing rows from id > {$lastid} to <= {$limitid}");
            $totalimported += self::import_batch($lastid, $limitid);
            $lastid = $limitid;
        }

        mtrace("  Import finished. Checkpoint advanced from id {$startedat} to {$lastid} ".
            "({$totalimported} rows imported this run).");
    }

    /**
     * Checks whether the core "Standard log" store (logstore_standard) is enabled, since that is
     * what populates logstore_standard_log, the sole data source for this task in scheduled mode.
     *
     * @return bool
     */
    protected static function is_standard_log_store_enabled(): bool {
        $enabled = explode(',', get_config('tool_log', 'enabled_stores') ?: '');
        return in_array('logstore_standard', $enabled);
    }

    /**
     * @param int $offsetid
     * @return bool true if there are rows in logstore_standard_log beyond $offsetid
     */
    protected static function has_more_rows(int $offsetid): bool {
        global $DB;
        return $DB->record_exists_select('logstore_standard_log', 'id > ?', [$offsetid]);
    }

    /**
     * Imports one batch of rows (offsetid, limitid] from logstore_standard_log, applying the
     * shared filtering rules, then atomically advances the stored checkpoint in the same
     * transaction as the data insert. If the task crashes/fails mid-batch, the whole transaction
     * is rolled back, so the checkpoint and the imported data can never fall out of sync - the
     * next run simply resumes from the previous checkpoint (no duplicates, no gaps).
     *
     * @param int $offsetid
     * @param int $limitid
     * @return int number of rows imported (after filtering)
     */
    protected static function import_batch(int $offsetid, int $limitid): int {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        $sql = "SELECT id, eventname, courseid, contextid, userid, timecreated
                  FROM {logstore_standard_log}
                 WHERE id > :offsetid AND id <= :limitid
              ORDER BY id";
        $rows = $DB->get_recordset_sql($sql, ['offsetid' => $offsetid, 'limitid' => $limitid]);

        $settings = filter::get_settings();
        $records = [];
        foreach ($rows as $row) {
            if (!filter::should_track_event((int) $row->courseid, (int) $row->userid, $settings)) {
                continue;
            }
            $record = new stdClass();
            $record->eventid = filter::resolve_eventid($row->eventname);
            $record->timecreated = $row->timecreated;
            $record->courseid = $row->courseid;
            $record->contextid = $row->contextid;
            $record->device = 0; // Historical rows carry no user-agent, so device is unknown.
            $records[] = $record;
        }
        $rows->close();

        if (count($records) !== 0) {
            $DB->insert_records('logstore_lanalytics_log', $records);
        }

        // Persisted in the same transaction as the data above -> atomic.
        set_config('lastimportedid', $limitid, 'logstore_lanalytics');

        $transaction->allow_commit();

        return count($records);
    }

    /**
     * Determines the logstore_standard_log id to resume importing from, derived from the most
     * recently written row already in logstore_lanalytics_log. This is only used when there is
     * no valid stored checkpoint (first run after switching to "scheduled" mode, or a checkpoint
     * that can no longer be trusted) - it is intentionally not run on every task execution since
     * it involves a comparatively expensive lookup.
     *
     * Matching strategy: find the row in logstore_standard_log with the same courseid, contextid
     * and timecreated as our most recent local row. contextid 0 in our table cannot be trusted to
     * uniquely identify a row (it can occur for e.g. global/site-level events), so in that case we
     * match on eventname instead of contextid.
     *
     * @return int the logstore_standard_log id to use as the initial checkpoint (0 = import all)
     */
    protected static function derive_starting_point(): int {
        global $DB;

        $lastlog = $DB->get_record_sql(
            "SELECT * FROM {logstore_lanalytics_log} WHERE id = (SELECT MAX(id) FROM {logstore_lanalytics_log})"
        );

        if (!$lastlog) {
            // No local data at all yet - start from the very beginning of logstore_standard_log.
            return 0;
        }

        $params = [
            'courseid' => $lastlog->courseid,
            'timecreated' => $lastlog->timecreated,
        ];
        $wheresql = "courseid = :courseid AND timecreated = :timecreated";

        if ((int) $lastlog->contextid !== 0) {
            $wheresql .= " AND contextid = :contextid";
            $params['contextid'] = $lastlog->contextid;
        } else {
            $eventname = $DB->get_field('logstore_lanalytics_evtname', 'eventname', ['id' => $lastlog->eventid]);
            if ($eventname !== false) {
                $wheresql .= " AND eventname = :eventname";
                $params['eventname'] = $eventname;
            }
        }

        $matchedid = $DB->get_field_sql("SELECT MAX(id) FROM {logstore_standard_log} WHERE {$wheresql}", $params);

        if ($matchedid) {
            return (int) $matchedid;
        }

        // No exact match found (e.g. logstore_standard_log was purged/rotated) - fall back to the
        // newest row at/before the same timestamp. This accepts a small risk of a duplicate or a
        // gap right at the boundary, which is preferable to silently re-importing everything.
        $fallbackid = $DB->get_field_sql(
            "SELECT MAX(id) FROM {logstore_standard_log} WHERE timecreated <= ?", [$lastlog->timecreated]
        );

        return (int) ($fallbackid ?: 0);
    }
}
