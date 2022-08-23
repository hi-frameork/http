<?php declare(strict_types=1);

namespace Hi\Http\Message\Stream;

use Hi\Http\Message\Stream;

/**
 * 以 stream 形式描述 php://memory 数据
 *
 * 通常，一个实例将包装一个PHP流；
 * 这个接口提供了一个包装最常见的操作，包括将整个流序列化为一个字符串。
 */
class Memory extends Stream
{
    public function __construct($mode = 'rb')
    {
        parent::__construct('php://memory', $mode);
    }
}
