<?php

namespace Hi\Http\Runtime\EventHandler;

use Hi\Http\Context;
use Hi\Http\EventHandler;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\Response\SwooleResponseFactory;
use Hi\Http\Runtime\SwooleServerRequestFactory;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

class SwooleEventHandler extends EventHandler
{
    /**
     * @var SwooleServerRequestFactory
     */
    protected $serverRequestFactory;

    /**
     * @var SwooleResponseFactory
     */
    protected $responseFactory;

    public function __construct()
    {
        $this->serverRequestFactory = new SwooleServerRequestFactory;
        $this->responseFactory = new SwooleResponseFactory;
    }

    /**
     */
    public function onRequest(Request $swRequest, Response $swResponse): void
    {
        try {
            $context = new Context(
                $this->serverRequestFactory->createServerRequest($swRequest),
                $this->responseFactory->createResponse($swResponse)
            );
            $response = call_user_func(
                $this->handleRequest,
                $context
            );
        } catch (Throwable $e) {
            $response = Handler::reportAndprepareResponse($e);
        }

       $response->end(); 
    }
}
