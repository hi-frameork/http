<?php

declare(strict_types=1);

namespace Hi\Server;

use Hi\Server\Http\Adapter\Swoole;

class Factory
{
    public static function newInstance(array $config): ServerInterface
    {
        return new Swoole($config);
    }
}
