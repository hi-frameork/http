<?php

namespace Hi\Http;

use Hi\Server;

abstract class Runtime extends Server
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
