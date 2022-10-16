<?php

namespace Hi\Http\Runtime;

use Hi\Http\Message\Stream;
use Hi\Http\Message\UploadedFile;
use InvalidArgumentException;

use function json_decode;

abstract class EventHandler
{
    /**
     * @var callable
     */
    protected $handleRequest;

    protected $contextClass = \Hi\Http\Context::class;

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
     */
    public function registerRequestHandle(callable $callback)
    {
        $this->handleRequest = $callback;
    }

    public function registerContextClass(string $class)
    {
        $this->contextClass = $class;
    }

    public function createContext($request, $response)
    {
        return new $this->contextClass($request, $response);
    }

    /**
     * 格式化上传文件
     */
    protected function processUploadFiles($files): array
    {
        if (!is_array($files)) {
            return [];
        }

        $uploadFiles = [];

        foreach ($files as $file) {
            if (is_array($file['error'])) {
                throw new InvalidArgumentException('不支持以 key 数组方式上传文件', 400);
            }
            $uploadFiles[] = new UploadedFile($file['tmp_name'], $file['size'], $file['error'], $file['name'], $file['type']);
        }

        return $uploadFiles;
    }

    /**
     * 解析请求内容体
     */
    protected function parseBody(string $contentType, $body): array
    {
        if (is_array($body)) {
            return $body;
        }

        $parts       = explode(';', $contentType);
        $contentType = trim($parts[0] ?? '');

        switch ($contentType) {
            case 'application/json':
                return json_decode($body, true) ?? [];

                break;
        }

        return [];
    }

    /**
     * 创建 stream body
     */
    protected function createStreamBody(string $content)
    {
        $body = new Stream('php://temp', 'rb+');
        $body->write($content);

        return $body;
    }
}
