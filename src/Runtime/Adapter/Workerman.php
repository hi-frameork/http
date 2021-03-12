<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\RuntimeTrait;
use Hi\Server\AbstractWorkermanServer;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Worker;
use Throwable;

class Workerman extends AbstractWorkermanServer
{
    use RuntimeTrait;

    /**
     * @var string
     */
    protected $socketName = '';

    /**
     * @var array
     */
    protected $eventHandle = [
        'onMessage',
    ];

    /**
     * HTTP 请求处理
     */
    public function onMessage(TcpConnection $connection, WorkerRequest $workerRequest)
    {
        try {
            $response = call_user_func(
                $this->handleRequest,
                (new Context($this->createServerRequest($workerRequest)))
            );
        } catch (Throwable $e) {
            $response = Handler::reportAndprepareResponse($e);
        }

        // 响应数据给客户端
        $connection->send(new WorkerResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->__toString()
        ));
    }

    /**
     * 返回包装客户端请求参数 ServerRequest 对象
     */
    protected function createServerRequest(WorkerRequest $request): ServerRequest
    {
        $rawBody = $request->rawBody();

        return new ServerRequest(
            $request->method(),
            $request->path(),
            [], // Woerkman 未提供 server 信息，囧
            $this->createStreamBody($rawBody),
            $request->header() ?? [],
            $request->cookie() ?? [],
            $request->get() ?? [],
            $this->processUploadFiles($request->file() ?? []),
            $this->parseBody($request->header('content-type', ''), $rawBody),
            (string) $request->protocolVersion()
        );
    }

    /**
     * 为 workerman 生成服务协议
     */
    protected function processSocketName()
    {
        $this->socketName = "http://{$this->host()}:{$this->port()}";
    }

    /**
     * 返回 HTTP 服务实例
     */
    protected function createServer(): Worker
    {
        $this->processSocketName();
        return new Worker($this->socketName);
    }
}
