<?php

namespace Rx\React;

use React\HttpClient\Request;
use React\HttpClient\Response;

class HttpResponseException extends \Exception
{

    /** @var  Request */
    private $request;

    /** @var Response */
    private $response;

    /**
     * HttpResponseException constructor.
     */
    public function __construct(Request $request, Response $response, $message = "", $code = 0, \Exception $previous = null)
    {
        $this->request  = $request;
        $this->response = $response;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }


}