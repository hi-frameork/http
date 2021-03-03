<?php declare(strict_types=1);

namespace Hi\Http;

use Psr\Http\Message\ServerRequestInterface;

class Request implements RequestInterface
{
    /**
     * @var ServerRequestInterface
     */
    protected $serverRequest;

    public function withServerRequest(ServerRequestInterface $serverRequest)
    {
        $this->serverRequest = $serverRequest;
    }

    public function getServerRequest(): ServerRequestInterface
    {
        return $this->serverRequest;
    }
}
