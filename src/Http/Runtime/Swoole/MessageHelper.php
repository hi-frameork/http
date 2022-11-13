<?php

namespace Hi\Http\Runtime\Swoole;

use Hi\Http\Message\Swoole\Response;
use Hi\Http\Message\Swoole\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;


trait MessageHelperTrait
{
    /**
     * 生成 Request 对象
     */
    protected function createServerRequest(SwooleRequest $request): ServerRequestInterface
    {
        $rawBody = $request->rawContent();

        return new ServerRequest(
            $request->server ?? [],
            $this->processUploadFiles($request->files),
            $request->cookie ?? [],
            $request->get    ?? [],
            $request->post ? $request->post : ($this->parseBody(($request->header['content-type'] ?? ''), $rawBody)),
            $request->server['request_method'] ?? '',
            $request->server['path_info']      ?? '',
            $request->header                   ?? [],
            $this->createStreamBody($rawBody),
            trim(strstr($request->server['server_protocol'], '/'), '/')
        );
    }

    /**
     * 生成 Response 对象
     */
    protected function createResponse(SwooleResponse $response): ResponseInterface
    {
        return (new Response())->withSwooleResponse($response);
    }
}
