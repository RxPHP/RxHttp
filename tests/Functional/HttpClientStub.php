<?php

namespace Rx\React\Tests\Functional;

use React\HttpClient\Client;
use React\HttpClient\Request;

class HttpClientStub extends Client
{

    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function request($method, $url, array $headers = [], $protocolVersion = '1.0')
    {
        return $this->request;
    }
}
