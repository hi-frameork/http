<?php

use Hi\Http\Context;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Pipeline;

require __DIR__ . '/vendor/autoload.php';

class A
{
    public function handle(Context $ctx, callable $next)
    {
        echo __METHOD__, PHP_EOL;;
        return $next($ctx);
    }
}

class B
{
    public function handle(Context $ctx, callable $next)
    {
        echo __METHOD__, PHP_EOL;;
        return $next($ctx);
    }
}

class C
{
    public function handle(Context $ctx, callable $next)
    {
        echo __METHOD__, PHP_EOL;;
        $ctx->response->getBody()->write(__METHOD__);
        return $next($ctx);
    }
}

$ctx = new Context(new ServerRequest());

/* @var Context $res */
(new Pipeline)
    ->send($ctx)
    ->throgh([
        A::class,
        B::class,
        C::class,
    ])
    ->then(function () {
        echo 'then', PHP_EOL;
    });
;


echo (string) $ctx->response->getBody();
