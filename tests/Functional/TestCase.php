<?php

namespace Rx\React\Tests\Functional;

use React\HttpClient\Request;
use React\Promise\Promise;
use React\Socket\ConnectorInterface;
use React\Stream\ReadableStreamInterface;
use Rx\React\HttpObservable;
use Rx\Scheduler;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected $connector;
    protected $stream;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder(ReadableStreamInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->connector = $this->createMock(ConnectorInterface::class);

        $this->connector->expects($this->once())
            ->method('connect')
            ->willReturnCallback(function () {

                return new Promise(function () {
                });
            });
    }

    protected function createHttpObservable(
        Request $request,
        string $method,
        string $url,
        string $body = null,
        array $headers = [],
        string $protocolVersion = '1.1'
    ): HttpObservable
    {
        $reflection      = new \ReflectionClass(HttpObservable::class);
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);

        $properties = [
            'client'          => new HttpClientStub($request),
            'method'          => $method,
            'url'             => $url,
            'body'            => $body,
            'headers'         => $headers,
            'protocolVersion' => $protocolVersion,
            'scheduler'       => Scheduler::getImmediate()
        ];

        $httpObservable = $reflection->newInstanceWithoutConstructor();

        foreach ($properties as $key => $property) {
            $p = $reflection->getProperty($key);
            $p->setAccessible(true);
            $p->setValue($httpObservable, $property);
        }

        return $httpObservable;
    }
}
