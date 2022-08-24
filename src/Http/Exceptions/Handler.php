<?php

declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Psr\Http\Message\ResponseInterface;
use Throwable;

class Handler
{
    // FIXME add report logic
    public static function reportAndprepareResponse(Throwable $e, ResponseInterface $response)
    {
    }
}
