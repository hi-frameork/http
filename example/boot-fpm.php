<?php

use Hi\Http\Application;
use Hi\Http\Router;
use Hi\Http\Runtime\RuntimeFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = new Application(['runtime' => RuntimeFactory::SWOOLE]);

$app->get('/', function () {
    return 'Hi, framework!';
});

$app->group(['/api' => function (Router $router) {
    $router->get('/user', function () {
        return 'user';
    });
    $router->post('/user', function () {
        return 'post-user';
    });
}]);

$app->listen();
