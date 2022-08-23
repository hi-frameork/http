<?php

namespace Hi\Http\Runtime;

use Hi\Http\EventHandler;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Throwable;

class SwooleEventHandler extends EventHandler
{
    public function __construct()
    {
        $this->factory = new SwooleServerRequestFactory;
    }

    public function onRequest(Request $request, Response $response): void
    {
        try {
            $response = call_user_func(
                $this->handleRequest,
                $this->factory->createServerRequest($request)
            );
        } catch (Throwable $e) {
            $response = Handler::reportAndprepareResponse($e);
        }

       $response->end(); 

        // // 设置响应 header 信息
        // foreach ($response->getHeaders() as $name => $value) {
        //     $swooleResponse->header($name, implode(', ', $value));
        // }

        // // HTTP statusCode
        // $swooleResponse->status($response->getStatusCode());
        // // 响应数据给客户端
        // $swooleResponse->end((string) $response->getBody());
    }
}
