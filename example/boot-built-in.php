<?php

use Hi\Http\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->get('/', function () {
    return 'Hi, framework!';
});

$app->listen();
