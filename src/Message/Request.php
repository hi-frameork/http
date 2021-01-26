<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;

final class Request implements RequestInterface
{
    public function __construct()
    {
    }

    public function withBody(StreamInterface $body)
    {
    }
}
