<?php

declare(strict_types=1);

namespace Hi\Http\Runtime\Builtin;

use function call_user_func;
use function file_get_contents;
use function header;

use Hi\Http\Context;

use Hi\Http\Message\Response;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\EventHandler as RuntimeEventHandler;

use function implode;
use function ob_clean;
use function str_ireplace;

use Throwable;

class EventHandler extends RuntimeEventHandler
{
    public function onRequest()
    {
        $response = new Response();

        try {
            /** @var Response $response */
            $response = call_user_func(
                $this->handleRequest,
                (new Context($this->createServerRequest(), $response))
            );
        } catch (Throwable $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(
                '<h1Internal Server Error</h1><p>' . $e->getMessage() . '</p>'
            );
        }

        // 清空内容缓冲区
        // 防止以 shell 脚本启动时输出 #!/usr/bin/env php
        ob_clean();

        // 设置响应 header 信息
        foreach ($response->getHeaders() as $name => $value) {
            header($name . ': ' . implode(', ', $value));
        }

        // HTTP statusCode
        header(
            $_SERVER['SERVER_PROTOCOL'] . ' ' .
            $response->getStatusCode() . ' ' .
            $response->getReasonPhrase()
        );

        echo (string) $response->getBody();
    }

    /**
     * 返回 Server Request 参数包装对象
     */
    protected function createServerRequest()
    {
        $headers = $this->parseHeaders();
        $rawBody = file_get_contents('php://input');

        return new ServerRequest(
            $_SERVER,
            $this->processUploadFiles($_FILES),
            $_COOKIE,
            $_GET,
            $this->parseBody($headers['CONTENT-TYPE'] ?? '', $rawBody),
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $headers,
            $this->createStreamBody($rawBody),
            trim(strstr($_SERVER['SERVER_PROTOCOL'], '/'), '/')
        );
    }

    /**
     * 解析客户端 headers
     */
    protected function parseHeaders(): array
    {
        $headers = [];

        // 从 SERVER 中提取 header 信息
        foreach ($_SERVER as $key => $value) {
            switch (true) {
                case (stripos($key, 'HTTP_', 0) !== false):
                    $name           = str_ireplace('HTTP_', '', $key);
                    $name           = str_ireplace('_', '-', $name);
                    $headers[$name] = $value;

                    break;
            }
        }

        return $headers;
    }
}
