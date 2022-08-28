<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Exception;
use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class Stream implements StreamInterface
{
    /**
     * @var resource|null
     */
    protected $resource;

    /**
     * @var int|null
     */
    protected $size;

    /**
     * @var bool
     */
    protected $seekable;

    /**
     * @var bool
     */
    protected $readable;

    /**
     * @var bool
     */
    protected $writeable;

    /**
     * 初始化流资源
     *
     * @param mixed  $stream 字符串流目标或流资源
     * @param string $mode   流资源操作模式
     *
     * @throws RuntimeException         流类型不对或者文件无法打开时抛出
     * @throws InvalidArgumentException 流类型或者文源类型不正确时抛出
     */
    public function __construct($stream = 'php://temp', $mode = 'w+b')
    {
        if (is_string($stream)) {
            $stream = $stream === '' ? false : @fopen($stream, $mode);
            if ($stream === false) {
                throw new RuntimeException('文件类型无法被打开');
            }
        }

        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('流资源类型或文件类型错误');
        }

        $this->resource = $stream;
    }

    /**
     * Closes the stream when the destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * 从头到尾将流中的所有数据读取到字符串。
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     *
     * 警告：这可能会尝试将大量数据加载到内存中。
     *
     * 这个方法 **必须** 在开始读数据前定位到流的开头，并读取出所有的数据。
     * 这个方法 **不得** 抛出异常以符合 PHP 的字符串转换操作。
     */
    public function __toString(): string
    {
        try {
            if (! $this->isReadable()) {
                return '';
            }

            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Exception $ex) {
            return '';
        }
    }

    /**
     * 关闭流和底层资源。
     */
    public function close(): void
    {
        if ($this->resource) {
            $resource = $this->detach();
            is_resource($resource) && ($resource);
        }
    }

    /**
     * 从流中分离底层资源。
     *
     * 分离之后，流处于不可用状态。
     *
     * @return resource|null 如果存在的话，返回底层 PHP 流。
     */
    public function detach()
    {
        $resource = $this->resource;
        $this->resource = $this->size = null;
        $this->seekable = $this->readable = $this->seekable = false;

        return $resource;
    }

    /**
     * 如果可知，返回以字节为单位的大小，如果未知返回 `null`。
     */
    public function getSize(): ?int
    {
        if ($this->size !== null) {
            return $this->size;
        }

        if ($this->resource === null) {
            return null;
        }

        $stat = fstat($this->resource);

        return $this->size = isset($stat['size']) ? $stat['size'] : null;
    }

    /**
     * 返回流指针的位置。
     *
     * @throws \RuntimeException 产生错误时抛出。
     */
    public function tell(): int
    {
        if (! $this->resource) {
            throw new RuntimeException('流不可用，无法获取指针位置');
        }

        if (($result = ftell($this->resource)) === false) {
            throw new RuntimeException('李璐指针位置获取失败');
        }

        return $result;
    }

    /**
     * 返回流指针是否位于末尾。
     */
    public function eof(): bool
    {
        return (!$this->resource || feof($this->resource));
    }

    /**
     * 返回流是否可随机读取。
     */
    public function isSeekable(): bool
    {
        if ($this->seekable !== null) {
            return $this->seekable;
        }

        return $this->seekable = ($this->resource && $this->getMetadata('seekable'));
    }

    /**
     * 为流指针设置指定位置
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset 要定位的流的偏移量。
     * @param int $whence 指定如何根据偏移量计算光标位置。有效值与 PHP 内置函数 `fseek()` 相同。
     *                    SEEK_SET：设定位置等于 $offset 字节。默认。
     *                    SEEK_CUR：设定位置为当前位置加上 $offset。
     *                    SEEK_END：设定位置为文件末尾加上 $offset （要移动到文件尾之前的位置，offset 必须是一个负值）。
     *
     * @throws \RuntimeException 失败时抛出。
     */
    public function seek($offset, $whence = SEEK_SET): void
    {
        if (! $this->resource) {
            throw new RuntimeException('流资源不可用，指针位置设置失败');
        }

        if (! $this->isSeekable()) {
            throw new RuntimeException('流指针不可设置位置');
        }

        if (fseek($this->resource, $offset, $whence) !== 0) {
            throw new RuntimeException('流指针位置设置出错');
        }
    }

    /**
     * 将文件指针设置为流起始位置
     * 如果流不可以随机访问，此方法将引发异常；否则将执行 seek(0)。
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     *
     * @throws \RuntimeException 失败时抛出。
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * 返回流是否可写。
     */
    public function isWritable(): bool
    {
        if ($this->writeable !== null) {
            return $this->writeable;
        }

        if (! is_string($mode = $this->getMetadata('mode'))) {
            return $this->writeable = false;
        }

        return $this->writeable = (strpbrk($mode, 'xwca+') !== false);
    }

    /**
     * 向流中写数据，并返回写入流的字节数
     *
     * @param string $string 要写入流的数据。
     *
     * @throws \RuntimeException 失败时抛出。
     */
    public function write($string): int
    {
        if (! $this->resource) {
            throw new RuntimeException('流可不用，写入失败');
        }

        if (! $this->isWritable()) {
            throw new RuntimeException('流不可写');
        }

        if (($result = fwrite($this->resource, $string)) === false) {
            throw new RuntimeException('流写入出错');
        }

        $this->size = null;

        return $result;
    }

    /**
     * 返回流是否可读。
     *
     * @return bool
     */
    public function isReadable()
    {
        if ($this->readable !== null) {
            return $this->readable;
        }

        if (! is_string($mode = $this->getMetadata('mode'))) {
            return $this->readable = false;
        }

        return $this->readable = (strpbrk($mode, 'r+') !== false);
    }

    /**
     * 从流中读取数据。
     *
     * 返回从流中读取的数据，如果没有可用的数据则返回空字符串
     *
     * @param int $length 从流中读取最多 $length 字节的数据并返回。
     *                    如果数据不足，则可能返回少于 $length 字节的数据。
     *
     * @throws \RuntimeException 失败时抛出。
     */
    public function read($length): string
    {
        if (! $this->resource) {
            throw new RuntimeException('流资源不可用，读取失败');
        }

        if (! $this->isReadable()) {
            throw new RuntimeException('流可不读');
        }

        if (($result = fread($this->resource, $length)) === false) {
            throw new RuntimeException('流读取出错');
        }

        return $result;
    }

    /**
     * 返回字符串中的剩余内容。
     *
     * @throws \RuntimeException 如果无法读取则抛出异常或者在读取时发生错误则抛出异常。
     */
    public function getContents(): string
    {
        if (! $this->isReadable()) {
            throw new RuntimeException('流不可读，无法获取内容');
        }

        if (($result = stream_get_contents($this->resource)) === false) {
            throw new RuntimeException('流内容读取出错');
        }

        return $result;
    }

    /**
     * 获取流中的元数据作为关联数组，或者检索指定的键。
     *
     * 返回的键与从 PHP 的 stream_get_meta_data() 函数返回的键相同。
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * @param string $key 要检索的特定元数据。
     *
     * @return array|mixed|null 如果没有键，则返回关联数组。如果提供了键并且找到值，
     *                          则返回特定键值；如果未找到键，则返回 null。
     */
    public function getMetadata($key = null)
    {
        if (! $this->resource) {
            return $key ? null : [];
        }

        $mete = stream_get_meta_data($this->resource);

        if ($key === null) {
            return $mete;
        }

        if (array_key_exists($key, $mete)) {
            return $mete[$key];
        }

        return null;
    }
}
