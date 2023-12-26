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

    protected array $processes = [];

    /**
     * @return $this
     */
    public function withRequestHandle(callable $callback)
    {
        $this->eventHandler->registerRequesthandle($callback);

        return $this;
    }

    public function withContextClass(string $class)
    {
        $this->eventHandler->registerContextClass($class);

        return $this;
    }

    public function withProcess($process)
    {
        $this->processes[] = $process;

        return $this;
    }

    public function withWorkerStartHandle(callable $callback)
    {
        $this->eventHandler->registerWorkerStartHandle($callback);
    }

    abstract public function task(string $taskClass, $data = null, int $delay = 0): bool;
}
