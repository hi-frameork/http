<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Message\ServerRequest;
use Hi\Http\Request;
use Hi\Http\Response;
use Hi\Server\AbstructSwooleServer;
use Swoole\Http\Server;

class Swoole extends AbstructSwooleServer
{
    /**
     * {@inheritdoc}
     */
    protected function createServer()
    {
        return new Server($this->host, $this->port);
    }

    /**
     * @var callable
     */
    protected $requestHanle;

    public function withRequestHandle(callable $callback)
    {
        $this->requestHanle = $callback;
        return $this;
    }


    public function onStart()
    {
        echo "Swoole http server is started at http://127.0.0.1:{$this->port}\n";
    }

    public function onRequest($swooleRequest, $swooleResponse)
    {
        $serverRequest = new ServerRequest(
            $swooleRequest->server['request_method'],
            $swooleRequest->server['request_uri'],
            $swooleRequest->server
        );

        $reqeust = new Request;
        $reqeust->withServerRequest($serverRequest);

        $response = new Response;

        call_user_func($this->requestHanle, $reqeust, $response);

        $swooleResponse->end($response->getContent());
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
