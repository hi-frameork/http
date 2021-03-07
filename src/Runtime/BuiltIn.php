<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Helpers\Json;
use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Message\UploadedFile;
use Hi\Server\AbstructBuiltInServer;
use Hi\Http\Exceptions\InvalidArgumentException;
use Throwable;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstructBuiltInServer
{
    public function start(int $port = 9527, string $host = '127.0.0.1'): void
    {
        if ('cli' === php_sapi_name()) {
            $this->processPort($port);
            $this->processHost($host);
            $this->runHttpServer();
        } else {
            $this->handle();
        }
    }

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

        // HTTP statusCode
        header($_SERVER['SERVER_PROTOCOL'] . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        // 发送 header 头信息
        foreach ($response->getHeaders() as $name => $value) {
            header($name . ' ' . implode(', ', $value));
        }

        echo (string) $response->getBody();
    }

    protected function createServerRequest()
    {
        return new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['REQUEST_URI'],
            $_SERVER,
            'php://input',
            $this->parseHeaders(),
            $_COOKIE,
            $_GET,
            $this->parseUploadFiles(),
            $this->parseBody($_SERVER['REQUEST_METHOD']),
            trim(strstr($_SERVER['SERVER_PROTOCOL'], '/'), '/')
        );
    }

    protected function parseUploadFiles(): array
    {
        $files = [];

        foreach ($_FILES as $upload) {
            if (is_array($upload['error'])) {
                throw new InvalidArgumentException('不支持以 key 数组方式上传文件', 400);
            }
            $files[] = new UploadedFile($upload['tmp_name'], $upload['size'], $upload['error'], $upload['name'], $upload['type']);
        }

        return $files;
    }

    protected function parseBody($method)
    {
        switch ($method) {
            case 'POST':
                return $_POST;
                break;

            case 'PUT':
                $content = file_get_contents('php://input');
                // FIXME 请求为 application/x-www-form-urlencoded
                parse_str($content, $result);
                if ($result) {
                    return $result;
                }
                // FIXME 请求为 application/json
                return Json::decode($content, true);
                break;
        }

        return [];
    }

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
}
