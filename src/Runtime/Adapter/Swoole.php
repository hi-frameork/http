<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\RuntimeTrait;
use Hi\Server\AbstructSwooleServer;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\Http\Server;
use Throwable;

class Swoole extends AbstructSwooleServer
{
    use RuntimeTrait;

    protected $eventHandle = [
        'onRequest',
        'onStart',
    ];

    public function onStart()
    {
        echo "Swoole http server is started at http://127.0.0.1:{$this->port}\n";
    }

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

        foreach ($response->getHeaders() as $name => $value) {
            $swooleResponse->header($name, implode(', ', $value));
        }

        $swooleResponse->status($response->getStatusCode());
        $swooleResponse->end((string) $response->getBody());
    }

    protected function createServerRequest(SwooleRequest $request)
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
    protected function createServer()
    {
        return new Server($this->host, $this->port);
    }
}
