<?php

$offsetid = 0;
$batch = 10000;

define('CLI_SCRIPT', true);

if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

require(dirname(__FILE__) . '/../../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$usage = "Applies data from `logstore_lanalytics_log` to lalog plugins.
This is mostly used for development.
  
Options:
    -h --help               Print this help.
    --clean                 Clear all tables of all lalog plugins.
    --startid=<value>       First ID to be imported, leave empty to import all events.
    --batch=<value>         How many logs to be handled in one batch. Defaults to 10000

Example:
php cli/apply.php
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'clean' => false,
    'startid' => 0,
    'batch' => 0,
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


function load_lalog_plugins() {
    global $CFG;

    $pluginman = \core_plugin_manager::instance();
    $lalogplugins = $pluginman->get_present_plugins('lalog');
    $loggers = [];
    foreach ($lalogplugins as $plugin) {
        $path = substr($plugin->component, 6);
        include_once($CFG->dirroot. "/local/learning_analytics/logs/{$path}/classes/lalog/logger.php");
        $loggers[] = "{$plugin->component}\\logger";
    }
    return $loggers;
}

// returns true if there is more data
function check_for_rows(int $offsetid) {
    global $DB;
    $row = $DB->get_records_sql("SELECT id FROM {logstore_lanalytics_log} WHERE id > ? LIMIT 1", [$offsetid]);
    return count($row) !== 0;
}

function log_rows() {
    global $DB;
    return $DB->count_records('logstore_lanalytics_log');
}

function apply_events($loggers, int $offsetid, int $limitid) {
    global $DB;

    // query unknown event names
    $sql = <<<SQL
        SELECT l.*
        FROM {logstore_lanalytics_log} l
        WHERE l.id > ? AND l.id <= ?
        ORDER BY id
SQL;
    $rows = $DB->get_records_sql($sql, [$offsetid, $limitid]);
    
    foreach ($loggers as $logger) {
        cli_writeln("    lalog: {$logger}");
        $logger::log($rows);
    }
}

$loggers = load_lalog_plugins();
$loggersCount = count($loggers);
cli_writeln("Number of `lalog` plugins: {$loggersCount}");
foreach ($loggers as $logger) {
    cli_writeln("  {$logger}");
}

$rowcount = log_rows();
cli_writeln("Number of rows inside `logstore_lanalytics_log`: {$rowcount}");

if ($options['clean']) {
    cli_writeln("Instructing lalog plugins to truncate data.");
    foreach ($loggers as $logger) {
        cli_writeln("  Truncating: {$logger}");
        $logger::truncate();
    }
}

cli_writeln("Applying log data to lalog plugins.");

$offsetid;
while (check_for_rows($offsetid)) {
    $limitid = $offsetid + $batch;
    cli_writeln("  Applying data from id > {$offsetid} to <= {$limitid}");

    apply_events($loggers, $offsetid, $limitid);

    $offsetid = $limitid;
}

cli_writeln("Process finished.");

