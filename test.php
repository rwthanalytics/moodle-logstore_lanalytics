<?php

require(__DIR__ . '/../../../../../config.php');

defined('MOODLE_INTERNAL') || die;

$pluginman = \core_plugin_manager::instance();
$lareportplugins = $pluginman->get_present_plugins('lareport');
$archivePlugins = []; // archive classes to use per course
if ($lareportplugins !== null) {
    foreach ($lareportplugins as $plugin) {
        $component = $plugin->component;
        $path = substr($component, 9);
        $archivefile = "{$CFG->dirroot}/local/learning_analytics/reports/{$path}/classes/archive.php";
        if (file_exists($archivefile)) {
            include_once($archivefile);
            $archiveClass = "{$plugin->component}\\archive";
            $archivePlugins[] = (object) [
                "key" => $path,
                "class" => $archiveClass,
            ];
            // $result = $archiveClass::values(74, time());
        }
    }
}

$ar = ['asdf' => 1];

$cutoffTimestamp = 1496821600;

$courseArchive = function ($courseid, $archivePlugins, $cutoffTimestamp) {
    $archiveData = [];
    $atleastonevalue = false;
    foreach ($archivePlugins as $plugin) {
        $result = $plugin->class::values($courseid, $cutoffTimestamp);
        if ($result !== null) {
            $archiveData[$plugin->key] = $result;
            $atleastonevalue = true;
        }
    }
    if (!$atleastonevalue) {
        return null;
    }
    return $archiveData;
};

$courses = get_courses();
foreach ($courses as $course) {
    if ($course->startdate >= $cutoffTimestamp) { // skip courses newer than the cutoff date
        continue;
    }
    echo $course->id . "\n";
    $archive = $courseArchive($course->id, $archivePlugins, $cutoffTimestamp);
    if ($archive !== null) { // no data to archive for that course
        $json = json_encode($archive);
        echo "{$json}\n\n";
    }
}
// $mappedData = array_map($mapCourse, $filteredCourses);
// print_r($mappedData);
