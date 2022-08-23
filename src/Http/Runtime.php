<?php

namespace Hi\Http;

use Hi\Server\Server;

abstract class Runtime extends Server
{
    protected $eventHandler;

    /**
     * @return $this
     */
    public function withRequestHandle(callable $callback)
    {
        $this->eventHandler->register('request', $callback);
        return $this;
    }
}
