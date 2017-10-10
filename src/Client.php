<?php

namespace Rx\React;

use function EventLoop\getLoop;
use React\EventLoop\LoopInterface;
use React\HttpClient\Client as HttpClient;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;

final class Client
{
    private $client;

    public function __construct(LoopInterface $loop, ConnectorInterface $connector = null)
    {
        $connector    = $connector ?: new Connector($loop);
        $this->client = new HttpClient($loop, $connector);
    }

    public static function createWithDefaults(): self
    {
        return new self(getLoop());
    }

    public function request(string $method, string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable($method, $url, $body, $headers, $protocolVersion, $this->client);
    }
}
