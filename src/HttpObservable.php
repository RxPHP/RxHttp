<?php

namespace Rx\React;

use React\Dns\Resolver\Factory;
use React\HttpClient\Response;
use Rx\Disposable\CallbackDisposable;
use Rx\Observable;
use Rx\ObserverInterface;
use Rx\Scheduler\ImmediateScheduler;
use Rx\SchedulerInterface;

class HttpObservable extends Observable
{

    /** @var  string */
    private $method;

    /** @var  string */
    private $url;

    /** @var  string */
    private $body;

    /** @var array */
    private $headers;

    /** @var string */
    private $protocolVersion;

    /** @var  boolean */
    private $bufferResults;

    /** @var  boolean */
    private $includeResponse;

    /** @var \React\HttpClient\Client */
    private $client;

    public function __construct($method, $url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true, $includeResponse = false)
    {
        $this->method          = $method;
        $this->url             = $url;
        $this->body            = $body;
        $this->headers         = $headers;
        $this->protocolVersion = $protocolVersion;
        $this->bufferResults   = $bufferResults;
        $this->includeResponse = $includeResponse;

        $loop               = \EventLoop\getLoop();
        $dnsResolverFactory = new Factory();
        $dnsResolver        = $dnsResolverFactory->createCached('8.8.8.8', $loop);
        $factory            = new \React\HttpClient\Factory();
        $this->client       = $factory->create($loop, $dnsResolver);

    }

    /**
     * @param ObserverInterface $observer
     * @param SchedulerInterface|null $scheduler
     * @return \Rx\Disposable\CompositeDisposable|\Rx\DisposableInterface
     */
    public function subscribe(ObserverInterface $observer, SchedulerInterface $scheduler = null)
    {

        $scheduler = $scheduler ?: new ImmediateScheduler();

        $buffer  = '';
        $request = $this->client->request($this->method, $this->url, $this->headers, $this->protocolVersion);

        $request->on('response', function (Response $response) use (&$buffer, $observer, $request, $scheduler) {
            $response->on('data', function ($data, Response $response) use (&$buffer, $observer, $request, $scheduler) {

                try {
                    //Http Errors
                    if ($response->getCode() < 200 || $response->getCode() >= 400) {
                        $error = new HttpResponseException($request, $response, $response->getReasonPhrase(), $response->getCode());
                        $observer->onError($error);
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

                if ($this->bufferResults) {
                    $data = $this->includeResponse ? [$buffer, $response, $request] : $buffer;
                    $scheduler->schedule(function () use ($observer, $data) {
                        $observer->onNext($data);
                    });
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
}
