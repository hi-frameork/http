<?php

namespace Hi\Http;

abstract class EventHandler
{
    /**
     * @var callable
     */
    protected $handleRequest;

    /**
     * 注册请求业务处理回调
     *
     * 各个 Runtime 只是负责提供请求接收与响应，不负责具体的业务处理过程
     * 具体的业务处理过程将会在这个回调中进行处理，回调原型定义如下：
     *
     * ```php
     * $callback = function (\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response) {
     *     // 业务处理
     *     // ....
     * };
     * ```
     *
     * @return void
     */
    public function registerRequestHandle(callable $callback)
    {
        $this->handleRequest = $callback;
    }
}
