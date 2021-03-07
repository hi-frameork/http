<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Context;
use Hi\Http\Message\Response;
use Throwable;

class Fpm extends BuiltIn
{
    public function start(int $port = 9527, string $host = '127.0.0.1'): void
    {
        try {
            $response = call_user_func(
                $this->handleRequest,
                (new Context($this->createServerRequest()))
            );
        } catch (Throwable $e) {
            $response = new Response();
            $response->getBody()->write($e->getMessage());
        }

        setcookie('abs', '1234');

        echo (string) $response->getBody();
    }
}
