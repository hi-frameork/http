<?php

declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Handler
{
    // FIXME add report logic
    public static function reportAndprepareResponse(
        Throwable $e,
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        $response = $response->withStatus(500);

        $content = [
            'url'     => $request->getUri()->__toString(),
            'message' => $e->getMessage(),
            'line'    => $e->getLine(),
            'file'    => $e->getFile(),
            'trace'   => $e->getTraceAsString(),
        ];
        $response->getBody()->write(json_encode($content));

        return $response;
    }
}
