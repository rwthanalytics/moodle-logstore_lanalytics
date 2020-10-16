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

// Meaning of the device value:
// 0__XX -> Browser (where XX is the ID of the browser)
// 0XX__ -> OS (where XX is the ID of the operating system)
// 00000 -> unknown
// 1__00 -> Special cases (non-browsers)
// 10100 -> Moodle API

const OS_MULTIPLIER = 100;

// Dont' change the keys (like 'Windows' or 'Opera') below,
// as they might be used by other plugins to reference the values
class devices {
    const BROWSER = [
            'unknown' => 0,
            'Moodle API' => 0,

            'Chrome' => 11,
            'Edge' => 12,
            'Firefox' => 13,
            'Internet Explorer' => 14,
            'Opera' => 15,
            'Safari' => 16,

            'Mobile' => 50, // Unknown mobile browser
    ];

    const OS = [
            'unknown' => 0,

            'macOS' => 10,

            'Linux' => 20,

            'Windows' => 30,
            'Windows XP' => 31,
            'Windows Vista' => 32,
            'Windows 7' => 33,
            'Windows 8' => 34,
            'Windows 8.1' => 35,
            'Windows 10' => 36,

            'Mobile' => 50,
            'iOS' => 60,
            'Android' => 70,
            
            'Moodle API' => 101,
    ];

    public static function get_browser() {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $browser_array = array(
            '/msie|trident/i'           => 'Internet Explorer',
            '/firefox|fxios/i'          => 'Firefox',
            '/opr|opios|opera/i'        => 'Opera',
            '/edge|edga|edgios|edg\//i' => 'Edge',
            '/chrome|crios/i'           => 'Chrome',
            '/safari/i'                 => 'Safari',
            '/mobile/i'                 => 'Mobile'
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
            '/windows nt 10/i'      => 'Windows 10',
            '/windows nt 6.3/i'     => 'Windows 8.1',
            '/windows nt 6.2/i'     => 'Windows 8',
            '/windows nt 6.1/i'     => 'Windows 7',
            '/windows nt 6.0/i'     => 'Windows Vista',
            '/windows nt 5.1/i'     => 'Windows XP',
            '/windows xp/i'         => 'Windows XP',
            '/Windows Mobile/i'     => 'Mobile',
            '/iphone/i'             => 'iOS',
            '/ipod/i'               => 'iOS',
            '/ipad/i'               => 'iOS',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i'        => 'macOS',
            '/android/i'            => 'Android',
            '/linux/i'              => 'Linux',
            '/ubuntu/i'             => 'Linux',
            '/webos/i'              => 'Mobile'
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

    public static function get_device() {
        $os = self::get_os();
        $browser = self::get_browser();
        return $os * OS_MULTIPLIER + $browser;
    }

    public static function browserIdToString(int $id): string {
        $result = array_search($id, self::BROWSER);
        if ($result === false) {
            return '[BROWSER NOT IN LIST]';
        }
        return $result;
    }

    public static function osIdToString(int $id): string {
        $result = array_search($id, self::OS);
        if ($result === false) {
            return '[OS NOT IN LIST]';
        }
        return $result;
    }
}