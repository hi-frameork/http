<?php

namespace Hi\Http\Runtime\Swoole;

use Hi\Http\Context;
use Hi\Http\Message\Swoole\Response;
use Hi\Http\Message\Swoole\ServerRequest;
use Hi\Http\Runtime\EventHandler as RuntimeEventHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Server as SwooleServer;
use Swoole\Server\Task as ServerTask;
use Swoole\Timer;
use Throwable;

class EventHandler extends RuntimeEventHandler
{
    /**
     * Http 请求回调 handle
     */
    public function onRequest(SwooleRequest $swRequest, SwooleResponse $swResponse): void
    {
        /** @var Response $response */
        $response = $this->createResponse($swResponse);

        try {
            $response = call_user_func(
                $this->handleRequest,
                $this->createContext($this->createServerRequest($swRequest), $response)
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

    public function onTask(SwooleServer $server, ServerTask $task)
    {
        $payload = $task->data;

        if (!is_array($payload)) {
            trigger_error(__METHOD__ . " parameter '\$data' type must be array", E_USER_WARNING);

            return false;
        }

        if ($payload['delay'] > 0) {
            Timer::after(
                $payload['delay'] * 1000,
                fn () => (new $payload['class']())->execute($payload['data'], $server, $task->id, $task->worker_id)
            );
        } else {
            (new $payload['class']())->execute($payload['data'], $server, $task->id, $task->worker_id);
        }

        return true;
    }
}
