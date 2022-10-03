# 🚀 Hi 框架 http 组件

超轻量 http 运行时组件，支持在 `swoole`, `workerman`, `php-fpm`, `php-builtin` 等容器中运行。

**使用示例：**


```php
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
$app->get('/', fn () => 'hi, framework!');

// 服务将会运行在 http://127.0.0.1:4000
$app->listen(4000);
```

也可以这样写：

```php
<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/vendor/autoload.php';

(new Application())
    ->use(function (Context $ctx, Closure $next) {
        return $next($ctx);
    })
    ->get('/', fn () => 'hi, framework!')
    ->listen(4000)
;

```

Enjoy 😜!
