<?php

declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Handler
{
    public static function prepareResponse(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $response = $response->withStatus(500);
        $response->getBody()->write(
            '<h1>Internal Server Error</h1><p>' . $e->getMessage() . '</p>'
        );

        return $response;
    }
}
