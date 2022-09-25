<?php

declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Runtime\Builtin\Server as BuiltinRuntime;
use Hi\Http\Runtime\Swoole\Server as SwooleRuntime;

class Factory
{
    public static function create(array $config = [])
    {
        switch ($config['runtime'] ?? '') {
            case 'swoole':
                return new SwooleRuntime($config);

            default:
                return new BuiltinRuntime($config);
        }
    }
}
