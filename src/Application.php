<?php declare(strict_types=1);

namespace Hi\Http;

use Hi\Helpers\Json;
use Hi\Http\Runtime\BuiltIn;
use Hi\Http\Runtime\Swoole;
use Hi\Http\Runtime\Workerman;
use Hi\Server\ServerInterface;

class Application
{
    /**
     * @var ServerInterface
     */
    protected $server;

    public function __construct(array $config = [])
    {
    }

    public function listen(int $port = 8000, string $host = '0.0.0.0')
    {
        $runtime = $this->getRuntime();
        $runtime
            ->withRequestHandle($this->createRequestHandle())
            ->start()
        ;
    }

    protected function getRuntime(): ServerInterface
    {
        return new Workerman;
    }

    private function createRequestHandle()
    {
        return function (RequestInterface $request, ResponseInterface $response) {
            $response->setContent('hi');
        };
    }
}

