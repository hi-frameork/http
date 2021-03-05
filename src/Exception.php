<?php declare(strict_types=1);

namespace Hi\Http;

class Exception extends \Exception
{
    /**
     * @var mixed
     */
    protected $addition;

    /**
     * constructor
     * @param mixed $runtime
     */
    public function __construct(string $message = '', int $code = -1, $addition = null)
    {
        $this->addition = $addition;
        parent::__construct($message, $code);
    }

    /**
     * 返回从异常 code 提取的 http statusCode
     * 如果 code 值范围不在 100 ~ 599 之间，返回 500
     *
     * 设计该策略目的在于希望将业务状态码与 http statusCode 相结合
     * 如此通过 code 前三位数字码即可知道服务所在问题
     */
    public function getStatusCode(): int
    {
        $status = $this->code % 1000;
        return ($status < 100 || $status > 599) ? 500 : $status;
    }

    /**
     * 返回 runtime 数据
     *
     * @return mixed
     */
    public function getAddition()
    {
        return $this->addition;
    }
}
