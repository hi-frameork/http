<?php

declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

class Fpm extends BuiltIn
{
    public function start(int $port = 9527, string $host = '127.0.0.1'): void
    {
        $this->handle();
    }
}
