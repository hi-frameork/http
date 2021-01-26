<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Server\AbstructFpmServer;

class Fpm extends AbstructFpmServer
{
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
