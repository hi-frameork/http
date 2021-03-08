<?php

use Hi\Http\Application;
use Hi\Http\Context;
use Hi\Http\Runtime\AdapterFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(['runtime' => AdapterFactory::WORKERMAN]);

$app->get('/', function () {
    return 'Hi, framework!';
});

$app->post('/server', function (Context $ctx) {
    return json_encode($ctx->request->getParsedBody());
});

$app->listen();
