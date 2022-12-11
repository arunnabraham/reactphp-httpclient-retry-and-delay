<?php

header('Content-Type: application/json');
$retryInfoPath = __DIR__ . '/retries.txt';
$retries = (int)file_get_contents($retryInfoPath);
file_put_contents($retryInfoPath, (string)($retries+1));
if ($retries >= 5) {
    http_response_code(200);
    echo json_encode([
        "status" => "ok"
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "Server Error"
    ]);
}
