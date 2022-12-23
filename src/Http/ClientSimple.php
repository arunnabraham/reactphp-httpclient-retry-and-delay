<?php

declare(strict_types=1);

namespace ArunabrahamPup\HttpRetryWithDelay\Http;

use Psr\Http\Message\ResponseInterface;
use React\Http\Browser;
use React\Promise\Promise;
use React\Promise\PromiseInterface;

use function React\Promise\Timer\sleep;

class ClientSimple
{
    public const RETRY_ERROR_CODES = [500, 429];
    public const MAX_RETRIES = 5;
    public const MIN_DELAY_SECONDS = 2;

    //private array $urlRetries = [];
    private int $retry = 0;
    private Browser $client;

    public function __construct()
    {
        $this->client = new Browser();
        //$this->retry = 0;
    }
    public function get($url): PromiseInterface
    {
        return $this->client->withRejectErrorResponse(false)->get(
            $url,
            ['Accept' => 'application/json']
        )->then(
            function (ResponseInterface $response) use ($url) {

                return match (in_array($response->getStatusCode(), self::RETRY_ERROR_CODES, true)) {
                    false => (function () use ($response, $url) {
                        return match ($response->getStatusCode()) {
                            200 => (function () use ($response) {
                                $this->retry = 0;
                                return $response;
                            })(),
                            default => $this->rejected($response, $url)
                        };
                    })(),
                    true => $this->retry($response, $url),
                    default => $this->rejected($response, $url)
                };
            },
            function (\Exception $error) use ($url) {
                $this->rejected($error, $url);
            }
        );
    }

    public function retry(ResponseInterface $response, string $url): PromiseInterface
    {
        if ($this->retry === self::MAX_RETRIES) {
            $this->retry = 0;
            return $this->rejected($response, $url);
        }
        $this->retry += 1;
        $delayTime =  $response->getStatusCode() === 429
            ? ($response->getHeader('Retry-After')[0] ?? self::MIN_DELAY_SECONDS)
            : self::MIN_DELAY_SECONDS;
        echo 'retrying.. ' . $this->retry . ' status: '. $response->getStatusCode(). ' delay: '. $delayTime;
        var_dump($response->getHeader('Retry-After'));
        return sleep($delayTime)->then(function () use ($url) {
            return $this->get($url);
        });
    }

    public function rejected(ResponseInterface|\Exception $response, string $url): PromiseInterface
    {
        $this->retry = 0;

        match (true) {
            ($response instanceof \Exception) => (function () use ($response) {
                echo $response->getMessage() . ' rejected response';
            })(),
            ($response instanceof ResponseInterface) => (function () use ($response) {
                echo $response->getBody()->getContents() . ' rejected response';
            })(),
            default => (function () {
                echo 'Unknown Error rejected response';
            })()
        };

        return new Promise(
            fn () => null
        );
    }
}
