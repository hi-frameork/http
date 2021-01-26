<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Server\AbstructBuiltInServer;

class BuiltIn extends AbstructBuiltInServer
{
    public function start(callable $handle, callable $taskHandle)
    {
        if ('cli' === php_sapi_name()) {
            $sep = DIRECTORY_SEPARATOR;

            $entryFilePath = rtrim($_SERVER['PWD'], $sep) . $sep . ltrim($_SERVER['SCRIPT_FILENAME'], $sep);

            $command = sprintf(
                '%s -S %s:%s %s',
                $this->findPhpExecutable(),
                $this->host,
                $this->port,
                $entryFilePath
            );

            passthru($command, $status);
        } else {
            echo time();
        }
    }

    public function restart()
    {
    }

    public function stop(bool $force = false)
    {
    }
}
