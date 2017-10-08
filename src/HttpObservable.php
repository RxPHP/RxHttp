<?php

namespace Rx\React;

use React\HttpClient\Client;
use React\HttpClient\Response;
use Rx\Disposable\CallbackDisposable;
use Rx\DisposableInterface;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\Scheduler;

final class HttpObservable extends Observable
{
    private $method;
    private $url;
    private $body;
    private $headers;
    private $protocolVersion;
    private $client;
    private $scheduler;
    private $includeResponse = false;
    private $bufferResults = true;

    public function __construct(string $method, string $url, string $body = null, array $headers = [], string $protocolVersion = '1.1', Client $client)
    {
        $this->method          = $method;
        $this->url             = $url;
        $this->body            = $body;
        $this->headers         = $headers;
        $this->protocolVersion = $protocolVersion;
        $this->scheduler       = Scheduler::getDefault();
        $this->client          = $client;
    }

    protected function _subscribe(ObserverInterface $observer): DisposableInterface
    {
        $this->setContentLength();

        $scheduler = $this->scheduler;
        $buffer    = '';
        $request   = $this->client->request($this->method, $this->url, $this->headers, $this->protocolVersion);

        $request->on('response', function (Response $response) use (&$buffer, $observer, $request, $scheduler) {
            $response->on('data', function ($data) use (&$buffer, $observer, $request, $scheduler, $response) {

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
    public function streamResults(): self
    {
        $this->bufferResults = false;

        return $this;
    }

    /**
     * The observable will emit an array that includes the data, request, and response.
     *
     * @return $this
     */
    public function includeResponse(): self
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

        if (!in_array('content-length', $headers, true)) {
            $this->headers['Content-Length'] = strlen($this->body);
        }
    }
}
