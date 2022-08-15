# Hi 框架 http 组件

超轻量 http 组建，统一 server 接口，支持在 `swoole`, `workerman`, `php-fpm`, `php-builtin` 等容器中运行。

**快速开始**

以 PHP 内建(Builtin) Webserver 快速实现带中间件接口。

```php
<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/vendor/autoload.php';

$app = new Application();

$app->get('/hi', function () {
    return 'Hi-framework';
});

$app->listen(9000);
```

访问 http://127.0.0.1:9000 查看结果。
