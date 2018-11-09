<?php
/*
 * Веб красивый такой
 */

use FW\Application\WebApplication;

require_once __DIR__ . '/bootstrap.php';

$Application = new WebApplication();
$Application->start();
