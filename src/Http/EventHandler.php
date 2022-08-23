<?php

namespace Hi\Http;

abstract class EventHandler
{
    /**
     * @var callable
     */
    protected $handleRequest;

    public function registerRequesthandle(callable $callback)
    {
        $this->handleRequest = $callback;
    }
}
