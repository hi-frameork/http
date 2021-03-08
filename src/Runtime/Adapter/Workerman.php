<?php declare(strict_types=1);

namespace Hi\Http\Runtime\Adapter;

use Hi\Http\Context;
use Hi\Http\Exceptions\Handler;
use Hi\Http\Message\ServerRequest;
use Hi\Http\Runtime\RuntimeTrait;
use Hi\Server\AbstructWorkermanServer;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request as WorkerRequest;
use Workerman\Protocols\Http\Response as WorkerResponse;
use Workerman\Worker;

class Workerman extends AbstructWorkermanServer
{
    use RuntimeTrait;

    /**
     * @var string
     */
    protected $socketName = '';

    protected $eventHandle = [
        'onMessage',
        'onWorkerStart',
    ];

    public function onWorkerStart()
    {
        echo "Workerman http server is started at {$this->socketName}\n";
    }

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

        $connection->send(new WorkerResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody()->__toString()
        ));
    }

    protected function createServerRequest(WorkerRequest $request)
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

    protected function processSocketName()
    {
        $this->socketName = "http://{$this->host}:{$this->port}";
    }

    protected function createServer()
    {
        $this->processSocketName();
        return new Worker($this->socketName);
    }
}
