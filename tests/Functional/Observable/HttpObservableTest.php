<?php

namespace Rx\React\Tests\Functional\Observable;

use React\HttpClient\Request;
use React\HttpClient\RequestData;
use React\HttpClient\Response;
use Rx\Observable;
use Rx\Observer\CallbackObserver;
use Rx\React\Tests\Functional\TestCase;

class HttpObservableTest extends TestCase
{

    /**
     * @test
     */
    public function http_with_buffer()
    {
        $testData1 = str_repeat("1", 1000);
        $testData2 = str_repeat("1", 1000);
        $testData3 = str_repeat("1", 1000);

        $error = false;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url);

        $source->subscribe(new CallbackObserver(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData1, $response]);
        $response->emit("data", [$testData2, $response]);
        $response->emit("data", [$testData3, $response]);
        $response->emit("end");

        $this->assertEquals($result, $testData1 . $testData2 . $testData3);
        $this->assertTrue($complete);
        $this->assertFalse($error);

    }


    /**
     * @test
     */
    public function http_without_buffer()
    {
        $testData = str_repeat("1", 1000); //1k, so it does not use the buffer
        $error    = false;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url);

        $source->subscribe(new CallbackObserver(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData, $response]);
        $response->emit("end");

        $this->assertEquals($result, $testData);
        $this->assertTrue($complete);
        $this->assertFalse($error);

    }

    /**
     * @test
     */
    public function http_with_stream()
    {
        $testData1 = str_repeat("1", 1000);
        $testData2 = str_repeat("1", 1000);
        $testData3 = str_repeat("1", 1000);

        $error   = false;
        $result  = false;
        $invoked = 0;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url)
            ->streamResults();

        $source->subscribe(new CallbackObserver(
            function ($v) use (&$result, &$invoked, &$value) {
                $result = true;
                $invoked++;
                $value .= $v;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData1, $response]);
        $response->emit("data", [$testData2, $response]);
        $response->emit("data", [$testData3, $response]);
        $response->emit("end");

        $this->assertTrue($result);
        $this->assertTrue($complete);
        $this->assertFalse($error);

        $this->assertEquals($invoked, 3);
        $this->assertEquals($value, $testData1 . $testData2 . $testData3);

    }

    /**
     * @test
     */
    public function http_with_error()
    {
        $testData = str_repeat("1", 1000); //1k, so it does not use the buffer
        $error    = false;
        $complete = false;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '500', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url);

        $source->subscribe(new CallbackObserver(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData, $response]);
        $response->emit("end");

        $this->assertNotEquals($result, $testData);
        $this->assertFalse($complete);
        $this->assertTrue($error);

    }

    /**
     * @test
     */
    public function http_with_includeResponse()
    {
        $testData = str_repeat("1", 1000); //1k, so it does not use the buffer
        $error    = false;
        $complete = false;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url)
            ->includeResponse();

        $source->subscribe(new CallbackObserver(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData, $response]);
        $response->emit("end");

        $this->assertEquals($result[0], $testData);
        $this->assertInstanceOf('React\HttpClient\Response', $result[1]);
        $this->assertInstanceOf('React\HttpClient\Request', $result[2]);
        $this->assertTrue($complete);
        $this->assertFalse($error);

    }

    /**
     * @test
     */
    public function http_with_includeResponse_with_buffer()
    {
        $testData1 = str_repeat("1", 1000);
        $testData2 = str_repeat("1", 1000);
        $testData3 = str_repeat("1", 1000);
        $complete  = false;
        $error     = false;

        $method      = "GET";
        $url         = "https://www.example.com";
        $requestData = new RequestData($method, $url);
        $request     = new Request($this->connector, $requestData);
        $response    = new Response($this->stream, 'HTTP', '1.0', '200', 'OK', ['Content-Type' => 'text/plain']);
        $source      = $this->createHttpObservable($request, $method, $url)
            ->includeResponse();

        $source->subscribe(new CallbackObserver(
            function ($value) use (&$result) {
                $result = $value;
            },
            function ($e) use (&$error) {
                $error = true;
            },
            function () use (&$complete) {
                $complete = true;
            }
        ));

        $request->emit("response", [$response]);
        $response->emit("data", [$testData1, $response]);
        $response->emit("data", [$testData2, $response]);
        $response->emit("data", [$testData3, $response]);
        $response->emit("end");

        $this->assertEquals($result[0], $testData1 . $testData2 . $testData3);
        $this->assertInstanceOf('React\HttpClient\Response', $result[1]);
        $this->assertInstanceOf('React\HttpClient\Request', $result[2]);
        $this->assertTrue($complete);
        $this->assertFalse($error);

    }

}
