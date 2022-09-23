<?php

declare(strict_types=1);

namespace Hi\Tests\Http\Message\TestAsset;

use Hi\Http\Message\Message as MessageMessage;
use Psr\Http\Message\StreamInterface;

class Message extends MessageMessage
{
    /**
     * @const string
     */
    public const DEFAULT_PROTOCOL_VERSION = '1.1';

    /**
     * @param resource|string|StreamInterface|mull $body
     * @param array                                $headers
     * @param string                               $protocol
     */
    public function __construct($body = null, array $headers = [], string $protocol = '')
    {
        $this->registerStream($body);
        $this->registerHeaders($headers);
        $this->registerProtocolVersion($protocol);
    }
}
