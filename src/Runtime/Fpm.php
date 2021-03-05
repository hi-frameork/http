<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Context;
use Hi\Http\Message\ServerRequest;
use Hi\Server\AbstructFpmServer;

class Fpm extends AbstructFpmServer
{
    /**
     * @var callable
     */
    protected $requestHanle;

    public function withRequestHandle(callable $callback)
    {
        $this->requestHanle = $callback;
        return $this;
    }

    public function start(): void
    {
        $request = new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SERVER_PORT'],
            $_SERVER
        );

        $context = new Context($request);
        call_user_func($this->handleRequest, $context);

        echo (string) $context->response->getBody();
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
