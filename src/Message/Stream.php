<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Exceptions\RuntimeException;
use Hi\Helpers\StatusCode;
use Hi\Helpers\Arr;
use Hi\Helpers\Exception;
use Psr\Http\Message\StreamInterface;
use function is_string;
use function is_resource;
use function fopen;
use function fclose;
use function fstat;
use function fread;
use function fseek;
use function ftell;
use function fwrite;
use function feof;
use function get_resource_type;
use function stream_get_contents;
use function stream_get_meta_data;
use function strpbrk;

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

    /**
     * {@inheritDoc}
     */
    public function eof(): bool
    {
        if ($this->handle) {
            return feof($this->handle);
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function getContents(): string
    {
        $this->checkHandle();
        $this->checkReadable();

        $data = stream_get_contents($this->handle);
        if ($data !== false) {
            return $data;
        }

        throw new RuntimeException(
            '从 file/stream 获取内容失败',
            StatusCode::E_500000,
            [
                'stream' => $this->stream,
                'data' => $data,
            ]
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getMetadata($key = null)
    {
        if ($this->handle === null) {
            return null;
        }

        $mateData = stream_get_meta_data($this->handle);

        if ($key === null) {
            return $mateData;
        }

        return Arr::get($mateData, $key, []);
    }

    /**
     * {@inheritDoc}
     */
    public function getSize()
    {
        if ($this->handle !== null) {
            $stats = fstat($this->handle);
            if ($stats !== false) {
                return Arr::get($stats, 'size', null);
            }
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function isReadable(): bool
    {
        $mode = (string) $this->getMetadata('mode');
        return false !== strpbrk($mode, 'r+');
    }

    /**
     * {@inheritDoc}
     */
    public function isSeekable(): bool
    {
        return (bool) $this->getMetadata('seekable');
    }

    /**
     * {@inheritDoc}
     */
    public function isWritable(): bool
    {
        $mode = (string) $this->getMetadata('mode');
        return false !== strpbrk($mode, 'xwca+');
    }

    /**
     * {@inheritDoc}
     */
    public function read($length): string
    {
        $this->checkHandle();
        $this->checkReadable();

        $data = fread($this->handle, $length);

        if ($data === false) {
            throw new RuntimeException(
                '从 file/stream 读取数据失败',
                StatusCode::E_500000,
                [
                    'stream' => $this->stream,
                    'length' => $length,
                ]
            );
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function rewind(): void
    {
        $this->seek(0);
    }
    
    /**
     * {@inheritDoc}
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->checkHandle();
        $this->checkReadable();

        $seeker = fseek($this->handle, $offset, $whence);

        if ($seeker !== 0) {
            throw new RuntimeException(
                '移动 file/stream 文件指针失败',
                StatusCode::E_500000,
                [
                    'stream' => $this->stream,
                    'offset' => $offset,
                    'whence' => $whence,
                ]
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function tell(): int
    {
        $this->checkHandle();

        $position = ftell($this->handle);

        if (! is_int($position)) {
            throw new Exception(
                '无法检索指针位置',
                StatusCode::E_500000,
                [
                    'steam' => $this->handle
                ]
            );
        }

        return $position;
    }

    /**
     * {@inheritDoc}
     */
    public function write($string): int
    {
        $this->checkHandle();
        $this->checkReadable();

        $bytes = fwrite($this->handle, $string);

        if ($bytes === false) {
            throw new RuntimeException(
                '写入 file/stream 失败',
                StatusCode::E_500000,
                [
                    'string' => $string,
                    'stream' => $this->stream,
                ]
            );
        }

        return $bytes;
    }

    /**
     * 为实例设置/替换 stream
     * @throws RuntimeException
     */
    public function setStream($stream, string $mode = 'rb'): void
    {
        $handle = $stream;

        if (is_string($stream)) {
            $handle = @fopen($stream, $mode);
        }

        if (! is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new RuntimeException(
                '提供 stream 不是有效的 string/resource 类型',
                StatusCode::E_500000,
                [
                    'stream' => $stream,
                    'mode' => $mode,
                ]
            );
        }

        $this->handle = $handle;
        $this->stream = $stream;
    }

    /**
     * 检查 stream handle 有效性
     * @throws RuntimeException
     */
    private function checkHandle(): void
    {
        if ($this->handle === null) {
            throw new RuntimeException(
                'stream resource 为空，请检查是否正确初始化',
                StatusCode::E_500000,
                [
                    'stream' => $this->stream,
                ]
            );
        }
    }

    /**
     * 检查当前 stream 资源是否可读
     * @throws RuntimeException
     */
    private function checkReadable(): void
    {
        if (! $this->isReadable()) {
            throw new RuntimeException(
                'stream resource 不可读',
                StatusCode::E_500000,
                [
                    'steam' => $this->stream,
                ]
            );
        }
    }
}
