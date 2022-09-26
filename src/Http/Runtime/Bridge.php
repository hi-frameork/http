<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Server;

abstract class Bridge extends Server
{
    /**
     * @var EventHandler
     */
    protected $eventHandler;

    /**
     * @return $this
     */
    public function withRequestHandle(callable $callback)
    {
        $this->eventHandler->registerRequesthandle($callback);

        return $this;
    }
}
