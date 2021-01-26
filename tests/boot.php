<?php

use Hi\Http\Application;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application();

$app->listen(9000);
