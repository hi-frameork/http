<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Helpers\Json;
use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\RuntimeTrait;
use Hi\Server\AbstractBuiltInServer;
use Throwable;

use function php_sapi_name;
use function call_user_func;
use function ob_clean;
use function header;
use function implode;
use function trim;
use function strstr;
use function stripos;
use function str_ireplace;
use function parse_str;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstractBuiltInServer
{
    use RuntimeTrait;

    /**
     * 启动 HTTP 服务或处理客户端请求
     */
    public function start(int $port = 9527, string $host = '127.0.0.1')
    {
        if ('cli' === php_sapi_name()) {
            $this->processPort($port);
            $this->processHost($host);
            $this->runHttpServer();
        } else {
            $this->handle();
        }
    }

    /**
     * 处理 HTTP 请求
     */
    protected function handle()
    {
        try {
            $response = call_user_func(
                $this->handleRequest,
                (new Context($this->createServerRequest()))
            );
        } catch (Throwable $e) {
            $response = Handler::reportAndprepareResponse($e);
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

        return new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER,
            'php://input',
            $headers,
            $_COOKIE,
            $_GET,
            $this->processUploadFiles($_FILES),
            $this->parseBody($headers['CONTENT-TYPE'] ?? ''),
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
                    $name = str_ireplace('HTTP_', '', $key);
                    $name = str_ireplace('_', '-', $name);
                    $headers[$name] = $value;
                    break;
            }
        }

        return $headers;
    }

    /**
     * 解析客户端请求 body
     */
    protected function parseBody($contentType): array
    {
        if ($_POST) {
            return $_POST;
        }

        switch ($contentType) {
            case 'application/json':
                return Json::decode(file_get_contents('php://input'), true);
                break;

            case 'application/x-www-form-urlencoded':
                parse_str(file_get_contents('php://input'), $result);
                return $result;
                break;
        }

        return [];
    }
}
