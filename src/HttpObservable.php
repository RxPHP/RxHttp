<?php

namespace Rx\React;

use React\Dns\Resolver\Factory;
use React\HttpClient\Response;
use Rx\Disposable\CallbackDisposable;
use Rx\Observable;
use Rx\ObserverInterface;
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

    /** @var \React\HttpClient\Client */
    private $client;

    public function __construct($method, $url, $body = null, array $headers = [], $protocolVersion = '1.0', $bufferResults = true)
    {
        $this->method          = $method;
        $this->url             = $url;
        $this->body            = $body;
        $this->headers         = $headers;
        $this->protocolVersion = $protocolVersion;
        $this->bufferResults   = $bufferResults;

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

        $buffer  = '';
        $request = $this->client->request($this->method, $this->url, $this->headers, $this->protocolVersion);

        $request->on('response', function (Response $response) use (&$buffer, $observer, $request) {
            $response->on('data', function ($data, Response $response) use (&$buffer, $observer, $request) {
                if ($response->getCode() < 200 || $response->getCode() >= 400) {
                    $error = new HttpResponseException($request, $response, $response->getReasonPhrase(), $response->getCode());
                    $observer->onError($error);
                    return;
                }
                //@todo need a way to also sent the response

                if ($this->bufferResults) {
                    $buffer .= $data;
                } else {
                    $observer->onNext($data);
                }
            });

            $response->on('error', function ($e) use ($observer) {
                $error = new \Exception($e);
                $observer->onError($error);

            });

            $response->on('end', function ($end = null) use (&$buffer, $observer) {

                if ($this->bufferResults) {
                    $observer->onNext($buffer);
                }

                $observer->onCompleted();

            });
        });
        $request->end($this->body);

        return new CallbackDisposable(function () use ($request) {
            $request->close();
        });
    }
}
