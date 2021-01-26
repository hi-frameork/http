<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Message\Request;
use Hi\Http\Message\Stream\Input;
use Hi\Server\AbstructBuiltInServer;

/**
 * PHP 内建 Webserver
 */
class BuiltIn extends AbstructBuiltInServer
{
    /**
     * {@inheritDoc}
     */
    public function start(callable $handle, callable $taskHandle)
    {
        if ('cli' === php_sapi_name()) {
            $this->runServer();
        } else {
            $request = new Request;
            $request->withBody(new Input);
            $response = call_user_func($handle, $request);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function restart()
    {
    }

    /**
     * {@inheritDoc}
     */
    public function stop(bool $force = false)
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
}
