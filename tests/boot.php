<?php

use Hi\Http\Application;
use Hi\Http\Context;
use Hi\Http\Runtime\RuntimeFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(['type' => RuntimeFactory::WORKERMAN]);

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
