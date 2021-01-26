<?php

declare(strict_types=1);

namespace Hi\Http;

use Hi\Http\Runtime\BuiltIn;
use Hi\Server\ServerInterface;

class Application
{
    /**
     * @var ServerInterface
     */
    protected $server;

    public function __construct(array $config = [])
    {
    }

    public function listen(int $port = 8000, string $host = '0.0.0.0')
    {
        $this
            ->getServer($port, $host)
            ->start(
                $this->createRequestHandle(),
                $this->createTaskHandle()
            )
        ;
    }

    protected function getServer(): ServerInterface
    {
        return new BuiltIn;
    }

    private function createRequestHandle()
    {
        return function () {};
    }

    private function createTaskHandle()
    {
        return function () {};
    }
}

