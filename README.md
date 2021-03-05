# Hi 框架 http 组件

超轻量 http 组建，统一 server 接口，支持在 `swoole`, `workerman`, `php-fpm`, `php-builtin` 等容器中运行。

**简单示例**

新建文件 `test.php`，内容如下：

```php
<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/vendor/autoload.php';

$app = new Application();

$app->use(function (Context $ctx, $next) {
    $ctx->response->getBody()->write('hi-framework');
    return $next($ctx);
});

$app->use(function (Context $ctx, $next) {
    $ctx->response->getBody()->write('Hi, 我是中间件');
    return $next($ctx);
});

$app->listen(9000);
```

执行 `php test.php`，访问浏览器即可看到内容。

默认使用 `php-builtin` 容器运行（即PHP内建webserver），更改运行容器，在 `Application` 实例化时传入 runtime 配置即可（使用 swoole 作为运行容器）：

```php
<?php

use Hi\Http\Application;
use Hi\Http\Context;
use Hi\Http\Runtime\RuntimeFactory;

require __DIR__ . '/vendor/autoload.php';

$app = new Application(['runtime' => RuntimeFactory::SWOOLE]);

$app->get('/hi', function () {
    return 'Hi-framework';
});

$app->use(function (Context $ctx, $next) {
    $ctx->response->getBody()->write((string)time());
    return $next($ctx);
});

$app->use(function (Context $ctx, $next) {
    $ctx->response->getBody()->write('你说什么');
    return $next($ctx);
});

$app->listen(9000);
```
