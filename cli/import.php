<?php

$offsetid = 0;
$batch = 10000;
$limit = 0;

define('CLI_SCRIPT', true);

if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

require(dirname(__FILE__) . '/../../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$usage = "Imports data from table `logstore_standard_log` into table `logstore_lanalytics_log`.

Options:
    -h --help               Print this help.
    --clean                 Clean the `logstore_lanalytics_log` table before running.
                            Be aware, that this options deletes all data from the
                            table `logstore_lanalytics_log`. This option should only
                            be used before activating the logstore in the settings.
    --startid=<value>       First ID to be imported, leave empty to import all events.
    --pastweeks=<value>     Instead of using startid you can use past-weeks to set how
                            much weeks from the pasts should be imported. The importer
                            will ignore all events that are older. Example: Set this
                            value to 3 to import only the logs from the last 3 weeks.
    --batch=<value>         How many logs to be handled in one batch. Defaults to 10000
    --limit=<value>         For testing/development purposes only. This set the max. ID
                            of the row to limit the number of rows to be imported.

Example:
php cli/import.php
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'clean' => false,
    'startid' => 0,
    'pastweeks' => 0,
    'batch' => 0,
    'limit' => 0,
], [
    'h' => 'help'
]);

if ($options['help']) {
    cli_writeln($usage);
    exit(0);
}

if ($options['startid']) {
    $offsetid = (int) $options['startid'] - 1; // -1 as this is treated as offset
}

if ($options['batch']) {
    $batch = (int) $options['batch'];
}

if ($options['limit']) {
    $limit = (int) $options['limit'];
}
if ($options['pastweeks']) {
    if ($offsetid !== 0) {
        cli_writeln("Please specify only startid or pastweeks but not both.");
        die();
    }
    $pastweeks = (int) $options['pastweeks'];
    $date = new \DateTime();
    $date->modify('Monday this week');
    $date->modify("-{$pastweeks} week");
    $timestamp = $date->getTimestamp();
    cli_writeln("Searching for first event with timecreated >= {$timestamp} (Monday {$pastweeks} weeks ago).");
    $row = $DB->get_records_sql("SELECT id FROM {logstore_standard_log} WHERE timecreated >= ? ORDER BY id LIMIT 1", [$timestamp]);
    $foundid = current($row)->id;
    $offsetid = $foundid - 1;
    cli_writeln("  Found row ID: {$foundid}");
}

function truncate_logs() {
    global $DB;
    $DB->execute("TRUNCATE {logstore_lanalytics_log}");
}

// returns true if there is more data
function check_for_rows(int $offsetid) {
    global $DB;
    $row = $DB->get_records_sql("SELECT id FROM {logstore_standard_log} WHERE id > ? LIMIT 1", [$offsetid]);
    return count($row) !== 0;
}

function identify_events(int $offsetid, int $limitid) {
    global $DB;

    // query unknown event names
    $sql = <<<SQL
        SELECT DISTINCT l.eventname
        FROM {logstore_standard_log} l
        LEFT JOIN {logstore_lanalytics_evtname} e
            ON e.eventname = l.eventname
        WHERE l.id > ? AND l.id <= ?
            AND e.id IS NULL
SQL;
    $rows = $DB->get_records_sql($sql, [$offsetid, $limitid]);
    
    if (count($rows) !== 0) { // insert event names into table
        $eventid = $DB->insert_records('logstore_lanalytics_evtname', $rows);
    }
}

function copy_rows(int $offsetid, int $limitid) {
    global $DB;
    
    $sql = <<<SQL
        INSERT INTO {logstore_lanalytics_log}
            (eventid, timecreated, courseid, contextid, device)
        SELECT
            e.id AS eventid,
            l.timecreated,
            l.courseid,
            l.contextid,
            0 AS device
        FROM {logstore_standard_log} l
        JOIN {logstore_lanalytics_evtname} e ON
            l.eventname = e.eventname
        WHERE l.id > ? AND l.id <= ?
        ORDER BY l.id
SQL;
    $rows = $DB->execute($sql, [$offsetid, $limitid]);
}

function log_rows() {
    global $DB;
    return $DB->count_records('logstore_lanalytics_log');
}

$rowcount = log_rows();
cli_writeln("Number of rows inside `logstore_lanalytics_log` before import: {$rowcount}");

if ($options['clean']) {
    cli_writeln("  Truncating table `logstore_lanalytics_log`.");
    truncate_logs();

    $rowcount = log_rows();
    cli_writeln("Number of rows inside `logstore_lanalytics_log` after TRUNCATE: {$rowcount}");
}

cli_writeln("Starting import.");

while (check_for_rows($offsetid) && ($limit === 0 || $offsetid < $limit)) {
    $limitid = $offsetid + $batch;
    cli_writeln("  Importing rows from > {$offsetid} to <= {$limitid}");

    identify_events($offsetid, $limitid);
    copy_rows($offsetid, $limitid);

    $offsetid = $limitid;
}

cli_writeln("Import finished.");

$rowcount = log_rows();
cli_writeln("Number of rows inside `logstore_lanalytics_log` after import:  {$rowcount}");

