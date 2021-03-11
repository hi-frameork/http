<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\RuntimeTrait;
use Hi\Server\AbstractSwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use Throwable;

/**
 * Swoole 服务运行容器
 */
class Swoole extends AbstractSwooleServer
{
    use RuntimeTrait;

    /**
     * @var array
     */
    protected $eventHandle = [
        'onRequest',
        'onStart',
    ];

    /**
     * 服务启动事件
     */
    public function onStart()
    {
        echo "Swoole http server is started at http://127.0.0.1:{$this->port}\n";
    }

    /**
     * HTTP 请求处理
     */
    public function onRequest(SwooleRequest $swooleRequest, SwooleResponse $swooleResponse)
    {
        try {
            $response = call_user_func(
                $this->handleRequest,
                (new Context($this->createServerRequest($swooleRequest)))
            );
        } catch (Throwable $e) {
            $response = Handler::reportAndprepareResponse($e);
        }

        // 设置响应 header 信息
        foreach ($response->getHeaders() as $name => $value) {
            $swooleResponse->header($name, implode(', ', $value));
        }

        // HTTP statusCode
        $swooleResponse->status($response->getStatusCode());
        // 响应数据给客户端
        $swooleResponse->end((string) $response->getBody());
    }

    /**
     * 返回包装客户端请求参数 ServerRequest 对象
     */
    protected function createServerRequest(SwooleRequest $request): ServerRequest
    {
        $rawBody = $request->rawContent();

        return new ServerRequest(
            $request->server['request_method'],
            $request->server['path_info'],
            $request->server ?? [],
            $this->createStreamBody($rawBody),
            $request->header ?? [],
            $request->cookie ?? [],
            $request->get ?? [],
            $this->processUploadFiles($request->files ?? []),
            $this->parseBody($request->header['content-type'] ?? '', $rawBody),
            trim(strstr($request->server['server_protocol'], '/'), '/')
        );
    }

    /**
     * 返回 swoole http server 实例
     */
    protected function createServer(): Server
    {
        return new Server($this->host, $this->port);
    }
}
