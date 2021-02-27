<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Http\Exceptions\RuntimeException;
use Hi\Helpers\Arr;
use Exception;
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

/**
 * PSR-7 Stream
 */
class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    protected $handle = null;

    /**
     * @var resource|string
     */
    protected $stream;

    /**
     * Stream constructor.
     *
     * @param string|resource $stream
     * @param string $mode
     */
    public function __construct($stream, string $mode = 'rb')
    {
        $this->setStream($stream, $mode);
    }

    /**
     * 关闭 stream
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 将 stream 中所有内容以 string 方式返回
     * 为了符合PHP的字符串转换操作，此方法不能引发异常
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * Warning: 如果 stream 中数据较大，将会消耗更多时间从内存加载数据
     */
    public function __toString(): string
    {
        try {
            if ($this->isReadable()) {
                if ($this->isSeekable()) {
                    $this->rewind();
                }

                return $this->getContents();
            }
        } catch (Exception $e) {}

        return '';
    }

    /**
     * 设置 stream
     *
     * @param string|resource $stream
     * @param string $mode
     */
    public function setStream($stream, string $mode = 'rb'): void
    {
        $handle = $stream;

        if (is_string($stream)) {
            $handle = @fopen($stream, $mode);
        }

        if (! is_resource($handle) || get_resource_type($handle) !== 'stream') {
            throw new RuntimeException('stream 不是有效的 string/resource 类型');
        }

        $this->handle = $handle;
        $this->stream = $stream;
    }

    /**
     * 关闭 stream 以及关联的资源
     */
    public function close()
    {
        $handle = $this->detach();
        if (null !== $handle) {
            fclose($handle);
        }
    }

    /**
     * 从 stream 中分离资源
     * 一旦执行分离，stream 将处于不可用
     *
     * @return resource|null 如果存在的话，返回底层 PHP 流
     */
    public function detach()
    {
        $handle       = $this->handle;
        $this->handle = null;

        return $handle;
    }

    /**
     * 返回 stream 大小（如果可知）
     *
     * @return int|null
     */
    public function getSize()
    {
        if (null !== $this->handle) {
            $stats = fstat($this->handle);

            if (false !== $stats) {
                return Arr::get($stats, 'size', null);
            }
        }

        return null;
    }

    /**
     * 返回文件读/写指针的当前位置
     */
    public function tell(): int
    {
        $this->checkHandle();

        $position = ftell($this->handle);
        if (! is_int($position)) {
            throw new RuntimeException('无法检索指针位置');
        }

        return $position;
    }

    /**
     * 如果指针位于 stream 末尾，返回 true
     */
    public function eof(): bool
    {
        if ($this->handle) {
            return feof($this->handle);
        }

        return true;
    }

    /**
     * 返回流是否 seekable
     */
    public function isSeekable(): bool
    {
        return (bool) $this->getMetadata("seekable");
    }

    /**
     * 重置 steam 指针至起始处
     *
     * 如果 stream 指针不可偏移，此方法将抛出异常；
     * 否则，文件指针将会偏移至起始处
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * 将 handle 指针偏移至特定位置
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        $this->checkHandle();
        $this->checkSeekable();

        $seeker = fseek($this->handle, $offset, $whence);

        if (0 !== $seeker) {
            throw new RuntimeException('无法对文件指针执行偏移');
        }
    }

    /**
     * 返回流是否可写
     */
    public function isWritable(): bool
    {
        $mode = (string) $this->getMetadata("mode");
        return false !== strpbrk($mode, "xwca+");
    }

    /**
     * 写入数据至 stream
     */
    public function write($string): int
    {
        $this->checkHandle();
        $this->checkWritable();

        $bytes = fwrite($this->handle, $string);

        if (false === $bytes) {
            throw new RuntimeException('无法写入 file/stream');
        }

        return $bytes;
    }

    /**
     * 返回 stream 是否可读
     */
    public function isReadable(): bool
    {
        $mode = (string) $this->getMetadata('mode');
        return false !== strpbrk($mode, 'r+');
    }

    /**
     * 从 stream 读取指定长度数据
     */
    public function read($length): string
    {
        $this->checkHandle();
        $this->checkReadable();

        $data = fread($this->handle, $length);

        if (false === $data) {
            throw new RuntimeException('无法读取 file/stream');
        }

        return $data;
    }

    /**
     * 以字符串形式返回 strean 内容
     */
    public function getContents(): string
    {
        $this->checkHandle();
        $this->checkReadable();

        $data = stream_get_contents($this->handle);

        if (false === $data) {
            throw new RuntimeException('无法从 file/stream 读取内容');
        }

        return $data;
    }

    /**
     * 获取 stream 元数据数组或获取指定 key 对应数据
     *
     * @param string|null $key
     * @return array|mixed|null
     */
    public function getMetadata($key = null)
    {
        if (null === $this->handle) {
            return null;
        }

        // @see https://www.php.net/manual/zh/function.stream-get-meta-data.php
        $metaData = stream_get_meta_data($this->handle);

        if (null === $key) {
            return $metaData;
        }

        return Arr::get($metaData, $key, []);
    }
    
    /**
     * 检查 handle 是否可用，否则抛出异常
     *
     * @throws RuntimeException
     */
    private function checkHandle(): void
    {
        if (null === $this->handle) {
            throw new RuntimeException('handle 资源无效');
        }
    }

    /**
     * 检查 handle 是否可读，否则抛出异常
     *
     * @throws RuntimeException
     */
    private function checkReadable(): void
    {
        if (true !== $this->isReadable()) {
            throw new RuntimeException('handle 不可读');
        }
    }

    /**
     * 检查 handle 是否可查找，否则抛出异常
     *
     * @throws RuntimeException
     */
    private function checkSeekable(): void
    {
        if (true !== $this->isSeekable()) {
            throw new RuntimeException('handle 不可查找');
        }
    }

    /**
     * 检查 handle 是否可写，否则抛出异常
     *
     * @throws RuntimeException
     */
    private function checkWritable(): void
    {
        if (true !== $this->isWritable()) {
            throw new RuntimeException('handle 不可写');
        }
    }
}
