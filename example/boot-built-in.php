<?php

use Hi\Http\Application;
use Hi\Http\Context;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->get('/', function () {
    return 'Hi, framework!';
});


$app->post('/server', function (Context $ctx) {
    return json_encode($ctx->request->getServerParams());
});

$app->listen();
