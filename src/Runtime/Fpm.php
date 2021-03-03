<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Message\ServerRequest;
use Hi\Http\Request;
use Hi\Http\Response;
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
        $serverRequest = new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SERVER_PORT'],
            $_SERVER
        );
        $request = new Request;
        $request->withServerRequest($serverRequest);
        $response = new Response;
        call_user_func($this->requestHanle, $request, $response);

        echo $response->getContent();
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
