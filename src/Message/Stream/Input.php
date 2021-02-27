<?php declare(strict_types=1);

namespace Hi\Http\Message\Stream;

use Hi\Http\Message\Stream;

/**
 * 以 stream 形式描述 php://input 数据
 *
 * 通常，一个实例将包装一个PHP流；
 * 这个接口提供了一个包装最常见的操作，包括将整个流序列化为一个字符串。
 */
class Input extends Stream
{
    /**
     * @var string
     */
    private $data = '';

    /**
     * @var bool
     */
    private $eof = false;

    /**
     * Input constructor.
     */
    public function __construct()
    {
        parent::__construct('php://input', 'rb');
    }

    /**
     * 以 string 形式从 stream 中读取所有内容
     *
     * 内容读取时指针必须重置至其实位置
     * 读取时必须读取至指针结束位置以确保内容读取完整
     *
     * 为了符合PHP的字符串转换操作，此方法不能引发异常。
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     */
    public function __toString(): string
    {
        if ($this->eof) {
            return $this->data;
        }

        $this->getContents();

        return $this->data;
    }

    /**
     * 获取 stream 内容
     */
    public function getContents($length = -1): string
    {
        if ($this->eof) {
            return $this->data;
        }

        $data       = stream_get_contents($this->handle, $length);
        $this->data = $data;

        if (-1 === $length || $this->eof()) {
            $this->eof = true;
        }

        return $this->data;
    }

    /**
     * stream 将总是不可写（只读）
     */
    public function isWritable(): bool
    {
        return false;
    }

    /**
     * 从 stream 读取数据
     */
    public function read($length): string
    {
        $data = parent::read($length);

        if (true !== $this->eof) {
            $this->data = $data;
        }

        if ($this->eof()) {
            $this->eof = true;
        }

        return $this->data;
    }
}
