<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Server\AbstructSwooleServer;

class Swoole extends AbstructSwooleServer
{
    protected function createServer()
    {
    }

    public function start(callable $handle, callable $taskHandle)
    {
    }

    public function restart()
    {
    }

    public function stop(bool $force = false)
    {
    }
}
