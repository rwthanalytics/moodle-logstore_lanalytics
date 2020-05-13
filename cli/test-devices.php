<?php

// This file is used to check if our device detection system works reliably
// You can add tests yourself by adding them to the $ualist array

define('CLI_SCRIPT', true);

if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

require(dirname(__FILE__) . '/../../../../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// some tested myself, some taken from official docs, some from other websites
$ualist = [
    ['Internet Explorer', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko'],
    ['Internet Explorer', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Trident/7.0; rv:11.0) like Gecko'],
    ['Internet Explorer', 'Windows 8.1', 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko'],
    ['Internet Explorer', 'Windows 8', 'Mozilla/5.0 (Windows NT 6.2; Trident/7.0; rv:11.0) like Gecko'],
    ['Internet Explorer', 'Windows 7', 'Mozilla/5.0 (Windows NT 6.1; Trident/7.0; rv:11.0) like Gecko'],
    ['Internet Explorer', 'Windows 8', 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.2)'],
    ['Internet Explorer', 'Windows 7', 'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; WOW64; Trident/6.0)'],
    ['Internet Explorer', 'Windows Vista', 'Mozilla/4.0 (compatible; MSIE 9.0; Windows NT 6.0)'],
    ['Internet Explorer', 'Windows XP', 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0)'],

    ['Firefox', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:75.0) Gecko/20100101 Firefox/75.0'],
    ['Firefox', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:68.0) Gecko/20100101 Firefox/68.0'],
    ['Firefox', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.10; rv:57.0) Gecko/20100101 Firefox/57.0'],
    ['Firefox', 'Linux', 'Mozilla/5.0 (X11; Linux i686; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'Linux', 'Mozilla/5.0 (Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'Linux', 'Mozilla/5.0 (X11; Ubuntu; Linux i686; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'Linux', 'Mozilla/5.0 (X11; Fedora; Linux x86_64; rv:76.0) Gecko/20100101 Firefox/76.0'],
    ['Firefox', 'iOS', 'Mozilla/5.0 (iPhone; CPU iPhone OS 10_15_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/25.0 Mobile/15E148 Safari/605.1.15'],
    ['Firefox', 'iOS', 'Mozilla/5.0 (iPad; CPU OS 10_15_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) FxiOS/25.0 Mobile/15E148 Safari/605.1.15'],
    ['Firefox', 'iOS', 'Mozilla/5.0 (iPod touch; CPU iPhone OS 10_15_4 like Mac OS X) AppleWebKit/604.5.6 (KHTML, like Gecko) FxiOS/25.0 Mobile/15E148 Safari/605.1.15'],
    ['Firefox', 'Android', 'Mozilla/5.0 (Android 10; Mobile; rv:68.0) Gecko/68.0 Firefox/68.0'],
    ['Firefox', 'Android', 'Mozilla/5.0 (Android 10; Mobile; LG-M255; rv:68.0) Gecko/68.0 Firefox/68.0'],
    ['Firefox', 'Android', 'Mozilla/5.0 (Android 4.4; Mobile; rv:41.0) Gecko/41.0 Firefox/41.0'],
    ['Firefox', 'Android', 'Mozilla/5.0 (Android 4.4; Tablet; rv:41.0) Gecko/41.0 Firefox/41.0'],

    ['Chrome', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36'],
    ['Chrome', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'Linux', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36'],
    ['Chrome', 'iOS', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/81.0.4044.124 Mobile/15E148 Safari/604.1'],
    ['Chrome', 'iOS', 'Mozilla/5.0 (iPad; CPU OS 13_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/81.0.4044.124 Mobile/15E148 Safari/604.1'],
    ['Chrome', 'iOS', 'Mozilla/5.0 (iPod; CPU iPhone OS 13_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/81.0.4044.124 Mobile/15E148 Safari/604.1'],
    ['Chrome', 'Android', 'Mozilla/5.0 (Linux; Android 10) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36'],
    ['Chrome', 'Android', 'Mozilla/5.0 (Linux; Android 10; SM-A102U) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36'],
    ['Chrome', 'Android', 'Mozilla/5.0 (Linux; Android 10; LM-X420) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36'],
    ['Chrome', 'Android', 'Mozilla/5.0 (Linux; Android 10; LM-Q710(FGN)) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36'],

    ['Safari', 'Windows 10', 'Mozilla/5.0 (Windows; U; Windows NT 10.0; en-US) AppleWebKit/603.1.30 (KHTML, like Gecko) Version/11.0 Safari/603.1.30'],
    ['Safari', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_4) AppleWebKit/600.7.12 (KHTML, like Gecko) Version/8.0.7 Safari/600.7.12'],
    ['Safari', 'iOS', 'Mozilla/5.0 (iPhone; CPU iPhone OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'],
    ['Safari', 'iOS', 'Mozilla/5.0 (iPhone; CPU iPhone OS 12_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/12.1 Mobile/15E148 Safari/604.1'],
    ['Safari', 'iOS', 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5376e Safari/8536.25'],
    ['Safari', 'iOS', 'Mozilla/5.0(iPad; U; CPU iPhone OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B314 Safari/531.21.10'],

    ['Edge', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/18.17763'],
    ['Edge', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 Edg/81.0.416.72'],
    ['Edge', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 Edg/81.0.416.72'],
    ['Edge', 'Android', 'Mozilla/5.0 (Linux; Android 10; HD1913) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 EdgA/45.3.4.4955'],
    ['Edge', 'Android', 'Mozilla/5.0 (Linux; Android 10; SM-G973F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 EdgA/45.3.4.4955'],
    ['Edge', 'Android', 'Mozilla/5.0 (Linux; Android 10; Pixel 3 XL) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 EdgA/45.3.4.4955'],
    ['Edge', 'Android', 'Mozilla/5.0 (Linux; Android 10; ONEPLUS A6003) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 EdgA/45.3.4.4955'],
    ['Edge', 'iOS', 'Mozilla/5.0 (iPhone; CPU iPhone OS 13_4_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/13.0 EdgiOS/45.3.19 Mobile/15E148 Safari/605.1.15'],
    ['Edge', 'Mobile', 'Mozilla/5.0 (Windows Mobile 10; Android 10.0; Microsoft; Lumia 950XL) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 Edge/40.15254.603'], // Windows 10 Mobile

    ['Opera', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.102 Safari/537.36 OPR/57.0.3098.102'],
    ['Opera', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36 OPR/43.0.2442.991'],
    ['Opera', 'Windows 10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 OPR/68.0.3618.63'],
    ['Opera', 'Windows 7', 'Opera/9.80 (Windows NT 6.1; WOW64) Presto/2.12.388 Version/12.18'],
    ['Opera', 'macOS', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_4) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 OPR/68.0.3618.63'],
    ['Opera', 'Linux', 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 OPR/68.0.3618.63'],
    ['Opera', 'Android', 'Mozilla/5.0 (Linux; Android 10; VOG-L29) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 OPR/55.2.2719'],
    ['Opera', 'Android', 'Mozilla/5.0 (Linux; Android 10; SM-G970F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 OPR/55.2.2719'],
    ['Opera', 'Android', 'Mozilla/5.0 (Linux; Android 10; SM-N975F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Mobile Safari/537.36 OPR/55.2.2719'],
    ['Opera', 'iOS', 'Mozilla/5.0 (iPad; CPU OS 11_0_3 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) OPiOS/16.0.3.120766 Mobile/15A432 Safari/9537.53'],
];

use logstore_lanalytics\devices;

$VERBOSE = false;
$total = count($ualist);
$totalcorrect = 0;

for ($i = 0; $i < $total; $i += 1) {
    $triple = $ualist[$i];
    $browserkey = $triple[0];
    $oskey = $triple[1];
    $browserid = devices::BROWSER[$browserkey];
    $osid = devices::OS[$oskey];

    $ua = $triple[2];
    
    $_SERVER['HTTP_USER_AGENT'] = $ua;
    $detectedbrowserid = devices::get_browser();
    $detectedosid = devices::get_os();
    $detectedbrowser = devices::browserIdToString($detectedbrowserid);
    $detectedos = devices::osIdToString($detectedosid);
    $correct = ($detectedbrowser === $browserkey && $detectedos === $oskey);
    $totalcorrect += $correct;
    $correctlabel = $correct ? '✔' : '✘';
    if ($VERBOSE || !$correct) {
        echo "Testing #{$i}: {$ua}\n";
        echo "  {$correctlabel} Detected: {$detectedbrowser} / {$detectedos}\n";
        if (!$correct) {
            echo "     Correct: {$browserkey} / {$oskey}\n";
        }
    }
}

$perc = 100 * ($totalcorrect / $total);
echo "Total correct: {$perc}% ({$totalcorrect}/{$total})\n";
