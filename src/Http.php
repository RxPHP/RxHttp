<?php

namespace Rx\React;

use Psr\Http\Message\RequestInterface;

final class Http
{
    public static function request(RequestInterface $request): HttpObservable
    {
        $method          = $request->getMethod();
        $url             = $request->getUri();
        $body            = $request->getBody()->getContents();
        $headers         = $request->getHeaders();
        $protocolVersion = $request->getProtocolVersion();

        return Client::createWithDefaults()->request($method, $url, $body, $headers, $protocolVersion);
    }

    public static function get(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('GET', $url, null, $headers, $protocolVersion);
    }

    public static function post(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('POST', $url, $body, $headers, $protocolVersion);
    }

    public static function put(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('PUT', $url, $body, $headers, $protocolVersion);
    }

    public static function delete(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('DELETE', $url, null, $headers, $protocolVersion);
    }

    public static function patch(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('PATCH', $url, $body, $headers, $protocolVersion);
    }

    public static function head(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return Client::createWithDefaults()->request('HEAD', $url, null, $headers, $protocolVersion);
    }
}
