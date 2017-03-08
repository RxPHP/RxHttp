<?php

namespace Rx\React;

use React\HttpClient\Request;
use React\HttpClient\Response;

class HttpResponseException extends \Exception
{
    private $request;
    private $response;
    private $body;

    public function __construct(Request $request, Response $response, string $message = "", int $code = 0, string $body = null, \Exception $previous = null)
    {
        $this->request  = $request;
        $this->response = $response;
        $this->body     = $body;
        parent::__construct($message, $code, $previous);
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
