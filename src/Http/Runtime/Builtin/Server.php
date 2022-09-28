<?php

declare(strict_types=1);

namespace Hi\Http\Runtime\Builtin;

use function escapeshellarg;

use function getenv;

use Hi\Http\Runtime\Bridge;
use Hi\Http\Runtime\TaskInterface;
use InvalidArgumentException;

use function is_executable;
use function is_file;
use function ltrim;
use function passthru;
use function php_sapi_name;
use function rtrim;
use function sprintf;

/**
 * @property EventHandler $eventHandler
 */
class Server extends Bridge
{
    /**
     * Server Construct
     */
    public function __construct(array $config = [])
    {
        parent::__construct($config);
        $this->eventHandler = new EventHandler();
    }

    /**
     * 启动 HTTP 服务或处理客户端请求
     */
    public function start(): void
    {
        if ('cli' === php_sapi_name()) {
            $this->runHttpServer();
        } else {
            $this->eventHandler->onRequest();
        }
    }

    /**
     * 在环境内 host 与 port 上启动内建 Webserver
     *
     * 相当于执行以下命令：
     *  php -S 127.0.0.1:9527 entry.php
     */
    protected function runHttpServer()
    {
        // 拼接入口文件完整路径
        $entryFilePath = rtrim($_SERVER['PWD'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;

        // 如果存在 public/index.php 文件，将其作为请求u入口文件
        $indexEntityFile = $entryFilePath . 'public' . DIRECTORY_SEPARATOR . 'index.php';
        if (is_file($indexEntityFile)) {
            $entryFile = $indexEntityFile;
        } else {
            $entryFile = $entryFilePath . ltrim($_SERVER['SCRIPT_FILENAME'], DIRECTORY_SEPARATOR);
        }

        // 拼接 PHP 内建 Webserver 启动指令
        $command = sprintf(
            '%s -S %s:%s %s',
            $this->phpExecutable(),
            $this->config->get('host'),
            $this->config->get('port'),
            $entryFile
        );

        passthru($command);
    }

    /**
     * 返回PHP可执行文件路径
     * 如果无法确定 PHP 可执行文件路径，返回空字符串
     */
    protected function phpExecutable(): string
    {
        if ($php = getenv('PHP_BINARY')) {
            if (!is_executable($php)) {
                $command = '\\' === \DIRECTORY_SEPARATOR ? 'where' : 'command -v';
                if ($php = strtok(exec($command . ' ' . escapeshellarg($php)), PHP_EOL)) {
                    if (!is_executable($php)) {
                        return '';
                    }
                } else {
                    return '';
                }
            }

            return $php;
        }

        if ($php = getenv('PHP_PATH')) {
            if (!@is_executable($php)) {
                return '';
            }

            return $php;
        }

        if ($php = getenv('PHP_PEAR_PHP_BIN')) {
            if (@is_executable($php)) {
                return $php;
            }
        }

        if (@is_executable($php = PHP_BINDIR . ('\\' === \DIRECTORY_SEPARATOR ? '\\php.exe' : '/php'))) {
            return $php;
        }

        return '';
    }

    public function task(string $taskClass, $data = null, int $delay = 0): bool
    {
        if (!is_a($taskClass, TaskInterface::class, true)) {
            throw new InvalidArgumentException(
                "Parameter \$taskClass[{$taskClass}] must implements \\Hi\\Http\\Runtime\\TaskInterface"
            );
        }

        return (new $taskClass())->execute($data);
    }
}
