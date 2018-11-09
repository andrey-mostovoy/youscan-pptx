<?php
/*
 * Обработчик запросов от приложения.
 */

use FW\Application\ApiApplication;

require_once __DIR__ . '/bootstrap.php';

$Application = new ApiApplication();

$responseData = '';

if (isset($_POST['method'])) {
    switch ($_POST['method']) {
        default:
            $responseData = [
                'error' => 'unknown method provided',
            ];
    }
} else {
    $responseData = [
        'result' => 'pong',
    ];
}

header('Content-Type: application/json');
echo json_encode($responseData);
