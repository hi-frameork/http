<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Exceptions\RuntimeException;
use Hi\Helpers\StatusCode;
use Psr\Http\Message\StreamInterface;
use function is_string;
use function fopen;

class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    protected $handle;

    /**
     * @var resource|string
     */
    protected $stream;

    public function __construct($stream, string $mode = 'rb')
    {
        $this->setStream($stream, $mode);
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * {@inheritDoc}
     */
    public function __toString()
    {
        if ($this->isReadable()) {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        }

        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function close()
    {
        if ($this->handle) {
            fclose($this->detach());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function detach()
    {
        $handle = $this->handle;
        $this->handle = null;

        return $handle;
    }

    public function eof()
    {
    }

    /**
     * 为实例设置/替换 stream
     */
    public function setStream($stream, string $mode = 'rb'): void
    {
        $handle = $stream;

        if (is_string($stream)) {
            $handle = fopen($stream, $mode);
        }

        if (! is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new RuntimeException('提供 stream 不是有效的 string/resource 类型', StatusCode::E_500000);
        }

        $this->handle = $handle;
        $this->stream = $stream;
    }
}
