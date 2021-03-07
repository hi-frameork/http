<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Context;
use Hi\Http\Message\ServerRequest;
use Hi\Server\AbstructWorkermanServer;

class Workerman extends AbstructWorkermanServer
{
    public function onMessage($connection, $workerRequest)
    {
        $request = new ServerRequest(
            $workerRequest->method(),
            $workerRequest->path()
        );

        $response = call_user_func($this->handleRequest, new Context($request));

        $connection->send((string) $response->getBody());
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
