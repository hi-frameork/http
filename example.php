<?php

use Hi\Http\Application;

require __DIR__ . '/vendor/autoload.php';

$app = new Application();

$app->get('/', fn () => 'hi');
$app->get('/time', fn () => time());

$app->listen(4000);
