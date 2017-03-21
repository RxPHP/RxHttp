<?php

namespace Rx\React;

use Psr\Http\Message\RequestInterface;

class Http
{
    public static function request(RequestInterface $request): HttpObservable
    {
        $method          = $request->getMethod();
        $url             = $request->getUri();
        $body            = $request->getBody()->getContents();
        $headers         = $request->getHeaders();
        $protocolVersion = $request->getProtocolVersion();

        return new HttpObservable($method, $url, $body, $headers, $protocolVersion);
    }

    public static function get(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('GET', $url, null, $headers, $protocolVersion);
    }

    public static function post(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('POST', $url, $body, $headers, $protocolVersion);
    }

    public static function put(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('PUT', $url, $body, $headers, $protocolVersion);
    }

    public static function delete(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('DELETE', $url, null, $headers, $protocolVersion);
    }

    public static function patch(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('PATCH', $url, $body, $headers, $protocolVersion);
    }

    public static function head(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return new HttpObservable('HEAD', $url, null, $headers, $protocolVersion);
    }
}
