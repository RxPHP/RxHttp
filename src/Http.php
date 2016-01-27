<?php

namespace Rx\React;

use Psr\Http\Message\RequestInterface;
use Rx\Observable;

class Http
{

    public static function request(RequestInterface $request, $bufferResults = true)
    {

        $method          = $request->getMethod();
        $url             = $request->getUri();
        $body            = $request->getBody()->getContents();
        $headers         = $request->getHeaders();
        $protocolVersion = $request->getProtocolVersion();

        return new HttpObservable($method, $url, $body, $headers, $protocolVersion, $bufferResults);
    }

    public static function get($url, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("GET", $url, null, $headers, $protocolVersion, $bufferResults);
    }

    public static function post($url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("POST", $url, $body, $headers, $protocolVersion, $bufferResults);
    }

    public static function put($url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("PUT", $url, $body, $headers, $protocolVersion, $bufferResults);
    }

    public static function delete($url, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("DELETE", $url, null, $headers, $protocolVersion, $bufferResults);
    }

    public static function patch($url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("PATCH", $url, $body, $headers, $protocolVersion, $bufferResults);
    }

    public static function head($url, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        return new HttpObservable("HEAD", $url, null, $headers, $protocolVersion, $bufferResults);
    }
}
