<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Helpers\Json;
use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Server\AbstructBuiltInServer;
use Hi\Http\Runtime\RuntimeTrait;
use Throwable;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstructBuiltInServer
{
    use RuntimeTrait;

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

        // 发送 header 头信息
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

    protected function createServerRequest()
    {
        $headers = $this->parseHeaders();

        return new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SCRIPT_NAME'],
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
