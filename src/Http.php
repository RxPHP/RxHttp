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

        return (new Client)->request($method, $url, $body, $headers, $protocolVersion);
    }

    public static function get(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('GET', $url, null, $headers, $protocolVersion);
    }

    public static function post(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('POST', $url, $body, $headers, $protocolVersion);
    }

    public static function put(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('PUT', $url, $body, $headers, $protocolVersion);
    }

    public static function delete(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('DELETE', $url, null, $headers, $protocolVersion);
    }

    public static function patch(string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('PATCH', $url, $body, $headers, $protocolVersion);
    }

    public static function head(string $url, array $headers = [], string $protocolVersion = '1.1'): HttpObservable
    {
        return (new Client)->request('HEAD', $url, null, $headers, $protocolVersion);
    }
}
