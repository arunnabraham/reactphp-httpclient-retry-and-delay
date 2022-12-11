<?php

declare(strict_types=1);

namespace ArunabrahamPup\HttpRetryWithDelay\Http;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\PromiseInterface;

use function React\Promise\Timer\sleep;

class Client
{

    const ERROR_CODES = [500, 429];
    const MAX_RETRIES = 5;
    const MIN_DELAY_SECONDS = 1.5;
    private array $urlRetries = [];
    private Browser $client;
    public function __construct()
    {
        $this->client = new Browser();
        $this->retry = 0;
    }
    public function get($url): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(false)->get(
            $url,
            ['Accept' => 'application/json']
        )->then(
            function (ResponseInterface $response) use ($url) {

                return $this->responseProcessor($response, $url);
            },
            function (\Exception $e) {
            }
        );
    }

    public function responseProcessor(ResponseInterface $response, string $url): ResponseInterface|PromiseInterface
    {
       return match($response->getStatusCode()) {
            200 => $response,
            500 => $this->retry($response, $url),
            429 => $this->retry($response, $url, (int)$response->getHeader('Retry-After')[0] ?? -1)
        };
    }

    public function retry(ResponseInterface $response, string $url, int $delaySeconds=0): PromiseInterface
    {
        if(!isset($this->urlRetries[$url]))
        {
            $this->urlRetries[$url] = 1;
        }
        if($this->urlRetries[$url] <= self::MAX_RETRIES) {
            echo 'retrying...';
            $this->urlRetries[$url]+=1;
            //delay
            return sleep($delaySeconds > 0 ? $delaySeconds : self::MIN_DELAY_SECONDS)->then(function () use ($url) {
                return $this->get($url);
            });
        }
    }
}
