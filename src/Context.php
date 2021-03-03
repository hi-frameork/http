<?php declare(strict_types=1);

namespace Hi\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Context
{
    protected $serverRequest;

    protected $response;

    public function setServerRequest(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
        return $this;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }

    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
        return $this;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
