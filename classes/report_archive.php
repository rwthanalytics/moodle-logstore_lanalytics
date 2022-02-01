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
 * Archive of data
 *
 * @package     logstore_lanalytics
 * @copyright   Lehr- und Forschungsgebiet Ingenieurhydrologie - RWTH Aachen University
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_lanalytics;

defined('MOODLE_INTERNAL') || die;

/**
 * Class report_archive
 *
 * @package logstore_lanalytics
 */
abstract class report_archive {
    public abstract static function values(int $courseid, int $maxtimecreated); // returns the data to archive (might be string, int, etc.)
    public abstract static function merge($archive1, $archive2); // merges two archives (from the same course) in case a previous archive needs to be merged
}