<?php

declare(strict_types=1);

namespace Hi\Http\Message\Swoole;

use Hi\Http\Message\Response as MessageResponse;
use Swoole\Http\Response as SwooleResponse;

class Response extends MessageResponse
{
    /**
     * Swoole 原生 Http Response
     */
    protected SwooleResponse $swooleResponse;

    /**
     * 挂载 Swoole Http Response
     *
     * @return $this
     */
    public function withSwooleResponse(SwooleResponse $response)
    {
        $this->swooleResponse = $response;

        return $this;
    }

    /**
     * 发送响应消息体给 client
     */
    public function send(): void
    {
        // 设置响应 header 信息
        foreach ($this->getHeaders() as $name => $value) {
            $this->swooleResponse->header($name, implode(', ', $value));
        }

        // HTTP statusCode
        $this->swooleResponse->status($this->getStatusCode());

        // 响应数据给客户端
        $this->swooleResponse->end((string) $this->getBody());
    }
}
