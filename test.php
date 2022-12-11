<?php

use ArunabrahamPup\HttpRetryWithDelay\Http\Client;
use Psr\Http\Message\ResponseInterface;

require_once __DIR__ . '/vendor/autoload.php';


$client = new Client();

$q = new Clue\React\Mq\Queue(3, null, function ($url) use ($client) {
    return $client->get($url);
});

$urls = [
    'http://localhost:4000/test.php',
    'http://localhost:4000/test2.php'
];
foreach ($urls as $url) {
    $q($url)->then(function (ResponseInterface $response) use ($url) {
        echo json_encode([
            'url' => $url,
            'statusCode' => $response->getStatusCode(),
            'body' =>  $response->getBody()->getContents()
        ]) . PHP_EOL;
    });
}
