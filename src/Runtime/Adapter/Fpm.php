<?php

declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

/**
 * PHP-FPM 服务入口
 */
class Fpm extends BuiltIn
{
    public function start(int $port = 9527, string $host = '127.0.0.1')
    {
        $this->handle();
    }
}
