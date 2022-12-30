<?php

namespace Hi\Http\Runtime\Swoole;

use Hi\Http\Message\FileResponse;
use Hi\Http\Message\Swoole\Response;
use Hi\Http\Runtime\EventHandler as RuntimeEventHandler;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Server as SwooleServer;
use Swoole\Server\Task as ServerTask;
use Swoole\Timer;
use Throwable;

class EventHandler extends RuntimeEventHandler
{
    use MessageHelperTrait;

    /**
     * Http 请求回调 handle
     */
    public function onRequest(SwooleRequest $swRequest, SwooleResponse $swResponse): void
    {
        $response = $this->createResponse();

        try {
            $response = call_user_func(
                $this->handleRequest,
                $this->createContext($this->createServerRequest($swRequest), $response)
            );
        } catch (Throwable $e) {
            $response = $response->withStatus(500);
            $trace    = str_replace('\n', '<br />', $e->getTraceAsString());
            $response->getBody()->write(
                '<h1>Internal Server Error</h1><p>' . $e->getMessage() . '</p><p>' . $trace . '</p>'
            );
        }

        /** @var Response $response */
        // 设置响应 header 信息
        foreach ($response->getHeaders() as $name => $value) {
            $swResponse->header($name, implode(', ', $value));
        }

        // HTTP statusCode
        $swResponse->status($response->getStatusCode());

        // 响应数据给客户端
        if ($response instanceof FileResponse) {
            $swResponse->sendfile($response->getBody()->__toString());
        } else {
            $swResponse->end($response->getBody()->__toString());
        }
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
