<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

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
