<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/vendor/autoload.php';

// 默认在 php-builtin 容器运行
$app = new Application();

// 注册中间件
$app->use(function (Context $ctx, Closure $next) {
    return $next($ctx);
});

// 路由定义
$app->get('/', fn () => 'hi');

// 服务将会运行在 http://127.0.0.1:4000
$app->listen(4000);
