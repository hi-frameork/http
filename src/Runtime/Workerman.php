<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Message\ServerRequest;
use Hi\Http\Request;
use Hi\Http\Response;
use Hi\Server\AbstructWorkermanServer;
use Workerman\Worker;

class Workerman extends AbstructWorkermanServer
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
        $worker = new Worker("http://{$this->host}:{$this->port}");
        $worker->onWorkerStart = [$this, 'onStart'];
        $worker->onMessage = [$this, 'onMessage'];
        Worker::runAll();
    }

    public function onStart()
    {
        echo "Workerman http server is started at http://127.0.0.1:{$this->port}\n";
    }

    public function onMessage($connection, $workerRequest)
    {
        $serverRequest = new ServerRequest(
            $workerRequest->method(),
            $workerRequest->path(),
        );

        $request = new Request;
        $request->withServerRequest($serverRequest);

        $response = new Response;

        call_user_func($this->requestHanle, $request, $response);

        $connection->send($response->getContent());
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
