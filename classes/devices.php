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

namespace logstore_lanalytics;

defined('MOODLE_INTERNAL') || die;

// Dont' change the keys (like 'Windows' or 'Opera') below,
// as they might be used by other plugins to reference the values
class devices {
    const BROWSER = [
            'unknown' => 0,
            'Moodle API' => 1,

            'Chrome' => 1000,
            'Edge' => 2000,
            'Firefox' => 3000,
            'Internet Explorer' => 4000,
            'Opera' => 5000,
            'Safari' => 6000,

            'Mobile' => 10000, // Unknown mobile browser
    ];

    const OS = [
            'unknown' => 0,
            'Moodle API' => 1,

            'Windows' => 1000,
            'Windows XP' => 1001,
            'Windows Vista' => 1002,
            'Windows 7' => 1003,
            'Windows 8' => 1004,
            'Windows 8.1' => 1005,
            'Windows 10' => 1006,

            'macOS' => 2000,

            'Linux' => 3000,

            // 10k+ -> mobile
            'Mobile' => 10000,
            'iOS' => 11000,
            'Android' => 12000,
    ];

    public static function get_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $browser_array = array(
                '/msie|trident/i'   =>  'Internet Explorer',
                '/firefox/i'        =>  'Firefox',
                '/chrome/i'         =>  'Chrome',
                '/safari/i'         =>  'Safari',
                '/edge/i'           =>  'Edge',
                '/opera/i'          =>  'Opera',
                '/opr/i'            =>  'Opera',
                '/mobile/i'         =>  'Mobile'
        );

        $browser = '';
        foreach ($browser_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $browser = $value;
                break;
            }
        }

        return self::BROWSER[$browser] ?? 0;
    }

    public static function get_os() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $os_array = array(
                '/windows nt 10/i'      =>  'Windows 10',
                '/windows nt 6.3/i'     =>  'Windows 8.1',
                '/windows nt 6.2/i'     =>  'Windows 8',
                '/windows nt 6.1/i'     =>  'Windows 7',
                '/windows nt 6.0/i'     =>  'Windows Vista',
                '/windows nt 5.1/i'     =>  'Windows XP',
                '/windows xp/i'         =>  'Windows XP',
                '/macintosh|mac os x/i' =>  'macOS',
                '/mac_powerpc/i'        =>  'macOS',
                '/linux/i'              =>  'Linux',
                '/ubuntu/i'             =>  'Linux',
                '/iphone/i'             =>  'iOS',
                '/ipod/i'               =>  'iOS',
                '/ipad/i'               =>  'iOS',
                '/android/i'            =>  'Android',
                '/webos/i'              =>  'Mobile'
        );

        $os_platform = '';
        foreach ($os_array as $regex => $value) {
            if (preg_match($regex, $user_agent)) {
                $os_platform = $value;
                break;
            }
        }

        return self::OS[$os_platform] ?? 0;
    }
}