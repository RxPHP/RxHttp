<?php

namespace Rx\React;

use React\Dns\Resolver\Factory;
use React\HttpClient\Response;
use Rx\Disposable\CallbackDisposable;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\Scheduler;
use Rx\SchedulerInterface;

class HttpObservable extends Observable
{
    private $method;

    private $url;

    private $body;

    private $headers;

    private $protocolVersion;

    private $bufferResults;

    private $includeResponse;

    private $scheduler;

    /** @var \React\HttpClient\Client */
    private $client;

    public function __construct(
        string $method,
        string $url,
        string $body = null,
        array $headers = [],
        string $protocolVersion = '1.1',
        bool $bufferResults = true,
        bool $includeResponse = false,
        SchedulerInterface $scheduler = null
    ) {
        $this->method          = $method;
        $this->url             = $url;
        $this->body            = $body;
        $this->headers         = $headers;
        $this->protocolVersion = $protocolVersion;
        $this->bufferResults   = $bufferResults;
        $this->includeResponse = $includeResponse;
        $this->scheduler       = $scheduler ?: Scheduler::getDefault();

        $loop               = \EventLoop\getLoop();
        $dnsResolverFactory = new Factory();
        $dnsResolver        = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        $factory            = new \React\HttpClient\Factory();
        $this->client       = $factory->create($loop, $dnsResolver);
    }

    protected function _subscribe(ObserverInterface $observer): DisposableInterface
    {
        $this->setContentLength();

        $scheduler = $this->scheduler;
        $buffer    = '';
        $request   = $this->client->request($this->method, $this->url, $this->headers, $this->protocolVersion);

        $request->on('response', function (Response $response) use (&$buffer, $observer, $request, $scheduler) {
            $response->on('data', function ($data, Response $response) use (&$buffer, $observer, $request, $scheduler) {

                try {
                    //Buffer the data if we get a http error
                    $code = $response->getCode();
                    if ($code < 200 || $code >= 400) {
                        $buffer .= $data;
                        return;
                    }

                    if ($this->bufferResults) {
                        $buffer .= $data;
                    } else {
                        $data = $this->includeResponse ? [$data, $response, $request] : $data;
                        $scheduler->schedule(function () use ($observer, $data) {
                            $observer->onNext($data);
                        });
                    }

                } catch (\Exception $e) {
                    $observer->onError($e);
                }
            });

            $response->on('error', function ($e) use ($observer) {
                $error = new \Exception($e);
                $observer->onError($error);

            });

            $response->on('end', function ($end = null) use (&$buffer, $observer, $request, $response, $scheduler) {

                $code = $response->getCode();
                if ($code < 200 || $code >= 400) {
                    $error = new HttpResponseException($request, $response, $response->getReasonPhrase(), $response->getCode(), $buffer);
                    $observer->onError($error);
                    return;
                }

                if ($this->bufferResults) {
                    $data = $this->includeResponse ? [$buffer, $response, $request] : $buffer;
                    $scheduler->schedule(function () use ($observer, $data, $scheduler) {
                        $observer->onNext($data);
                        $scheduler->schedule(function () use ($observer) {
                            $observer->onCompleted();
                        });
                    });

                    return;
                }

                $scheduler->schedule(function () use ($observer) {
                    $observer->onCompleted();
                });

            });
        });
        $request->end($this->body);

        return new CallbackDisposable(function () use ($request) {
            $request->close();
        });
    }

    /**
     * Will not buffer the result.
     *
     * @return $this
     */
    public function streamResults()
    {
        $this->bufferResults = false;

        return $this;
    }

    /**
     * The observable will emit an array that includes the data, request, and response.
     *
     * @return $this
     */
    public function includeResponse()
    {
        $this->includeResponse = true;

        return $this;
    }

    /**
     * Adds if needed a `Content-Length` header field to the request.
     */
    private function setContentLength()
    {
        if (!is_string($this->body)) {
            return;
        }

        $headers = array_map('strtolower', array_keys($this->headers));

        if (!in_array('content-length', $headers)) {
            $this->headers['Content-Length'] = strlen($this->body);
        }
    }
}
