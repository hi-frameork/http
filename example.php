<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/vendor/autoload.php';

$app = new Application();

// 注册中间件
$app->use(function (Context $ctx, Closure $next) {
    return $next($ctx);
});

// 路由定义
$app->get('/', fn () => 'hi');

$app->listen(4000);
