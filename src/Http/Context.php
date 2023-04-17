<?php

declare(strict_types=1);

namespace Hi\Http;

use Exception;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Router\Route;
use Psr\Http\Message\ServerRequestInterface;

class Context
{
    /**
     * @var Route
     */
    public $route;

    /**
     * @var array
     */
    public $attributes;

    /**
     * 响应内容类型
     *
     * @see https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Headers/Content-Type
     */
    public $contentType = 'application/json';

    /**
     * Context construct.
     */
    public function __construct(public ServerRequestInterface|ServerRequest $request)
    {
    }

    /**
     * 断言并抛出异常（如果条件为 false）
     * 此方法用于在条件不满足时快速抛出异常，有助于简化代码
     *
     * @param mixed  $condition 条件
     * @param int    $status    HTTP statusCode
     * @param string $message   错误信息
     */
    public function assert($condition, $status, $message = ''): void
    {
        if (!$condition) {
            throw new Exception($message, $status);
        }
    }

    /**
     * 设置响应 Content-Type
     *
     * 使用示例：
     * ```php
     * $ctx->setContentType('text/plain');
     * ```
     */
    public function setContentType(string $type)
    {
        $this->contentType = $type;
    }
}
