<?php declare(strict_types=1);

namespace Hi\Http;

use Psr\Http\Message\ServerRequestInterface;

interface RequestInterface
{
    public function withServerRequest(ServerRequestInterface $serverRequest);

    public function getServerRequest(): ServerRequestInterface;
}
