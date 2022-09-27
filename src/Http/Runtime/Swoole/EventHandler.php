<?php

namespace Hi\Http\Runtime\Swoole;

use Hi\Http\Context;
use Hi\Http\Message\Swoole\Response;
use Hi\Http\Message\Swoole\ServerRequest;
use Hi\Http\Runtime\EventHandler as RuntimeEventHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwRequest;
use Swoole\Http\Response as SwResponse;
use Throwable;

class EventHandler extends RuntimeEventHandler
{
    /**
     * Http 请求回调 handle
     */
    public function onRequest(SwRequest $swRequest, SwResponse $swResponse): void
    {
        /** @var Response $response */
        $response = $this->createResponse($swResponse);

        try {
            $response = call_user_func(
                $this->handleRequest,
                new Context($this->createServerRequest($swRequest), $response)
            );
        } catch (Throwable $e) {
            $response = $response->withStatus(500);
            $response->getBody()->write(
                '<h1Internal Server Error</h1><p>' . $e->getMessage() . '</p>'
            );
        }

        $response->send();
    }

    /**
     * 生成 Request 对象
     */
    protected function createServerRequest(SwRequest $request): ServerRequestInterface
    {
        $rawBody = $request->rawContent();

        return new ServerRequest(
            $request->server ?? [],
            $this->processUploadFiles($request->files),
            $request->cookie ?? [],
            $request->get    ?? [],
            $request->post ? $request->post : ($this->parseBody(($request->header['content-type'] ?? ''), $rawBody)),
            $request->server['request_method'],
            $request->server['path_info'],
            $request->header ?? [],
            $this->createStreamBody($rawBody),
            trim(strstr($request->server['server_protocol'], '/'), '/')
        );
    }

    /**
     * 生成 Response 对象
     */
    protected function createResponse(SwResponse $response): ResponseInterface
    {
        return (new Response())->withSwooleResponse($response);
    }
}
