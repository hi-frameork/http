<?php

declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Hi\Http\Context;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class Handler
{
    public static function prepareResponse(
        Throwable $e,
        Context $ctx,
    ): ResponseInterface {
        $response = $ctx->response->withStatus(500);
        $response->getBody()->write(
            '<h1>Internal Server Error</h1><p>' . $e->getMessage() . '</p>'
        );

        return $response;
    }
}
