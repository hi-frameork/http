<?php

use Hi\Http\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->get('/hi', function () {
    return 'Hi-framework';
});

$app->listen(9000);
