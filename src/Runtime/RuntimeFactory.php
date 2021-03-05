<?php declare(strict_types=1);

namespace Hi\Http\Runtime;

use RuntimeException;

class RuntimeFactory
{
    const BUILT_IN = 1;

    const FPM = 2;

    const SWOOLE = 3;

    const WORKERMAN = 4;

    public static function createInstance(array $config = [])
    {
        $runtimeType = $config['runtime'] ?? static::BUILT_IN;

        switch ($runtimeType) {
            case static::BUILT_IN:
                return new BuiltIn($config);
                break;

            case static::FPM:
                return new Fpm($config);
                break;

            case static::SWOOLE:
                return new Swoole($config);
                break;

            case static::WORKERMAN:
                return new Workerman($config);
                break;

            default:
                throw new RuntimeException('运行时 ' . $runtimeType . ' 不被支持');
        }
    }
}
