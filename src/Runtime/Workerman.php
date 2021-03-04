<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Context;
use Hi\Http\Message\ServerRequest;
use Hi\Server\AbstructWorkermanServer;
use Workerman\Worker;

class Workerman extends AbstructWorkermanServer
{
    public function onMessage($connection, $workerRequest)
    {
        $request = new ServerRequest(
            $workerRequest->method(),
            $workerRequest->path(),
        );

        $context = new Context($request);

        call_user_func($this->requestHanle, $context);

        $connection->send((string) $context->response->getBody());
    }

    public function restart(): void
    {
    }

    public function stop(bool $force = false): void
    {
    }
}
