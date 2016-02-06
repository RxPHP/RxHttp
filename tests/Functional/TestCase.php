<?php


namespace Rx\React\Tests\Functional;


use React\HttpClient\Request;
use React\Promise\Promise;
use Rx\React\HttpObservable;

class TestCase extends \PHPUnit_Framework_TestCase
{

    protected $connector;
    protected $stream;

    public function setUp()
    {
        $this->stream = $this->getMockBuilder('React\Stream\Stream')
            ->disableOriginalConstructor()
            ->getMock();

        $this->connector = $this->getMock('React\SocketClient\ConnectorInterface');

        $this->connector->expects($this->once())
            ->method('create')
            ->willReturnCallback(function () {

                return new Promise(function () {
                });
            });
    }

    /**
     * @param Request $request
     * @param $method
     * @param $url
     * @param null $body
     * @param array $headers
     * @param string $protocolVersion
     * @param bool $bufferResults
     * @param bool $includeResponse
     * @return HttpObservable
     */
    protected function createHttpObservable(Request $request, $method, $url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true, $includeResponse = false)
    {

        $reflection      = new \ReflectionClass('Rx\React\HttpObservable');
        $client_property = $reflection->getProperty('client');
        $client_property->setAccessible(true);

        $properties = [
            'client'          => new HttpClientStub($request),
            'method'          => $method,
            'url'             => $url,
            'body'            => $body,
            'headers'         => $headers,
            'protocolVersion' => $protocolVersion,
            'bufferResults'   => $bufferResults,
            'includeResponse' => $includeResponse
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