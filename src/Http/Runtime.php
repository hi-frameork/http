<?php

namespace Hi\Http;

use Hi\Server;

abstract class Runtime extends Server
{
    /**
     * @var EventHandler
     */
    protected $handler;

    /**
     * @return $this
     */
    public function withRequestHandle(callable $callback)
    {
        $this->handler->registerRequesthandle($callback);
        return $this;
    }
}
