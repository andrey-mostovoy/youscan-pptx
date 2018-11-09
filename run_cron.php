<?php
/*
 * Крон какой-нибудь
 */

use FW\Application\CronApplication;

require_once __DIR__ . '/bootstrap.php';

$Application = new CronApplication();

$startTime = time();
$endDate = date('Y-m-d H:i:00', $startTime + 60);
$endTime = strtotime($endDate);

$arguments = $argv;
array_shift($arguments);

$iterationsPerMinute = 10;

foreach ($arguments as $cronArgument) {
    list ($cmd, $val) = explode('=', $cronArgument);

    switch ($cmd) {
        case 'i':
            if ($val > 0 && $val <= 22) {
                $iterationsPerMinute = $val;
            } else {
                echo 'iteration count too big. check limits' . PHP_EOL;
            }
            break;
        default:
            echo 'No case for ' . $cmd . PHP_EOL;
    }
}

/**
 * максимум минуту живем
 */
$sleepTime = 60 / $iterationsPerMinute;

while (time() < $endTime) {
    sleep($sleepTime);
}
