<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Message 基类方法
 */
class AbstractMessage extends AbstractCommon
{
    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers;

    /**
     * HTTP 协议版本
     * 例如，“1.1”、“1.0”
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @var UriInterface
     */
    protected $uri;
}
