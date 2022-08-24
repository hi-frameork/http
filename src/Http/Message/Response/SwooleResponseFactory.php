<?php

declare(strict_types=1);

namespace Hi\Http\Message\Response;

use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Response;

class SwooleResponseFactory
{
    public function createResponse(Response $response): ResponseInterface
    {}
}
