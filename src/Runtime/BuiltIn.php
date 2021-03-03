<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Message\ServerRequest;
use Hi\Http\Request;
use Hi\Http\Response;
use Hi\Server\AbstructBuiltInServer;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstructBuiltInServer
{
    /**
     * @var callable
     */
    protected $requestHanle;

    /**
     * {@inheritDoc}
     */
    public function start(): void
    {
        if ('cli' === php_sapi_name()) {
            $this->runServer();
        } else {
            $this->handle();
        }
    }

    public function withRequestHandle(callable $callback)
    {
        $this->requestHanle = $callback;
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function restart(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function stop(bool $force = false): void
    {
    }

    public function runServer()
    {
        // 生成入口文件完整路径
        $separator     = DIRECTORY_SEPARATOR;
        $entryFilePath = rtrim($_SERVER['PWD'], $separator) . $separator . ltrim($_SERVER['SCRIPT_FILENAME'], $separator);

        // 拼接 PHP 内建 server 启动完整指令
        $command = sprintf(
            '%s -S %s:%s %s',
            $this->findPhpExecutable(),
            $this->host,
            $this->port,
            $entryFilePath
        );

        passthru($command, $status);
    }

    protected function handle()
    {
        $serverRequest = new ServerRequest(
            $_SERVER['REQUEST_METHOD'],
            $_SERVER['SERVER_PORT'],
            $_SERVER
        );

        $request = new Request;
        $request->withServerRequest($serverRequest);

        $response = new Response;
        call_user_func($this->requestHanle, $request, $response);

        echo $response->getContent();
    }
}
