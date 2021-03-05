<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Context;
use Hi\Http\Message\ServerRequest;
use Hi\Server\AbstructBuiltInServer;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstructBuiltInServer
{
    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        if ('cli' === php_sapi_name()) {
            $this->runHttpServer();
        } else {
            $this->handle();
        }
    }

    protected function handle()
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
}
