<?php declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Runtime\Adapter\BuiltIn;
use Hi\Http\Runtime\Adapter\Fpm;
use Hi\Http\Runtime\Adapter\Swoole;
use Hi\Http\Runtime\Adapter\Workerman;
use Hi\Server\AbstructServer;
use RuntimeException;

/**
 * Server runtime Factory
 *
 * 使用示例：
 * <?php
 *     // 获取指定运行时实例
 *     $runtime = AdapterFactory::createInstance(['runtime' => AdapterFactory::BUILT_IN]);
 *     $runtime
 *         ->withRequestHanle(function ($ctx) { return new \Hi\Http\Message\Response(); })
 *         ->start(9000)
 *     ;
 * ?>
 *
 * 打开浏览器，访问 http://127.0.0.1:9000 开始访问
 */
class AdapterFactory
{
    /**
     * 代表使用 PHP 内建 Webserver 运行
     */
    const BUILT_IN = 1;

    /**
     * 代表以 FPM 模型运行
     */
    const FPM = 2;

    /**
     * 代表使用 swoole 作为 http 容器运行
     */
    const SWOOLE = 3;

    /**
     * 代表使用 workerman 作为 http 容器运行
     */
    const WORKERMAN = 4;

    /**
     * 创建 http 运行容器实例
     * 如果未制定运行时容器，默认使用 builtIn 模式启动服务
     *
     * @param array $config http server 运行配置
     */
    public static function createInstance(array $config = []): AbstructServer
    {
        $runtimeType = $config['runtime'] ?? static::BUILT_IN;

        // 标记当前环境运行时容器
        define('SERVER_RUNTIME', $runtimeType);

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
