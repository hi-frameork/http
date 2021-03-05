<?php declare(strict_types=1);

namespace Hi\Http\Message;

use RuntimeException;
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
 * 描述数据流。
 *
 * 通常，实例将包装PHP流; 此接口提供了最常见操作的包装，包括将整个流序列化为字符串。
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
     * 从头到尾将流中的所有数据读取到字符串。
     *
     * 这个方法 **必须** 在开始读数据前定位到流的开头，并读取出所有的数据。
     *
     * 警告：这可能会尝试将大量数据加载到内存中。
     *
     * 这个方法 **不得** 抛出异常以符合 PHP 的字符串转换操作。
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
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
     * 如果可知，获取流的数据大小。
     *
     * @return int|null 如果可知，返回以字节为单位的大小，如果未知返回 `null`。
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
     * 返回当前读/写的指针位置。
     *
     * @throws \RuntimeException 产生错误时抛出。
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
     * 返回是否位于流的末尾。
     */
    public function eof(): bool
    {
        if ($this->handle) {
            return feof($this->handle);
        }

        return true;
    }

    /**
     * 返回流是否可随机读取。
     */
    public function isSeekable(): bool
    {
        return (bool) $this->getMetadata("seekable");
    }

    /**
     * 定位流的起始位置。
     *
     * 如果流不可以随机访问，此方法将引发异常；否则将执行 seek(0)。
     *
     * @see seek()
     * @see http://www.php.net/manual/en/function.fseek.php
     * @throws \RuntimeException 失败时抛出。
     */
    public function rewind(): void
    {
        $this->seek(0);
    }

    /**
     * 定位流中的指定位置。
     *
     * @see http://www.php.net/manual/en/function.fseek.php
     * @param int $offset 要定位的流的偏移量。
     * @param int $whence 指定如何根据偏移量计算光标位置。有效值与 PHP 内置函数 `fseek()` 相同。
     *     SEEK_SET：设定位置等于 $offset 字节。默认。
     *     SEEK_CUR：设定位置为当前位置加上 $offset。
     *     SEEK_END：设定位置为文件末尾加上 $offset （要移动到文件尾之前的位置，offset 必须是一个负值）。
     * @throws \RuntimeException 失败时抛出。
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
     * 返回流是否可写。
     */
    public function isWritable(): bool
    {
        $mode = (string) $this->getMetadata("mode");
        return false !== strpbrk($mode, "xwca+");
    }

    /**
     * 向流中写数据。
     * 返回写入流的字节数。
     *
     * @param string $string 要写入流的数据。
     * @throws \RuntimeException 失败时抛出。
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
     * 从流中读取数据。
     * 返回从流中读取的数据，如果没有可用的数据则返回空字符串。
     *
     * @param int $length 从流中读取最多 $length 字节的数据并返回。如果数据不足，则可能返回少于
     *     $length 字节的数据。
     * @throws \RuntimeException 失败时抛出。
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
     * 返回字符串中的剩余内容。
     *
     * @throws \RuntimeException 如果无法读取则抛出异常。
     * @throws \RuntimeException 如果在读取时发生错误则抛出异常。
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
     * 获取流中的元数据作为关联数组，或者检索指定的键。
     *
     * 返回的键与从 PHP 的 stream_get_meta_data() 函数返回的键相同。
     *
     * @see http://php.net/manual/en/function.stream-get-meta-data.php
     * @param string $key 要检索的特定元数据。
     * @return array|mixed|null 如果没有键，则返回关联数组。如果提供了键并且找到值，
     *     则返回特定键值；如果未找到键，则返回 null。
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
