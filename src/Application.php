<?php declare(strict_types=1);

namespace Hi\Http;

use Hi\Http\Runtime\AdapterFactory as RuntimeFactory;
use Hi\Http\Middleware\MiddlewareInterface;
use InvalidArgumentException;
use Throwable;
use Hi\Http\Exceptions\Handler;

/**
 * @method get(string $pattern, callable $handle)
 * @method post(string $pattern, callable $handle)
 * @method put $pattern, callable $handle)
 * @method delete(string $pattern, callable $handle)
 * @method head(string $pattern, callable $handle)
 * @method options(string $pattern, callable $handle)
 * @method patch(string $pattern, callable $handle)
 * @method group(array $config)
 */
class Application
{
    /**
     * @var \Hi\Server\AbstructServer
     */
    protected $runtime;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array
     */
    protected $middlewares = [];

    /**
     * @var callable
     */
    protected $handleRequest;

    /**
     * @var callable
     */
    protected $handleThrow;

    /**
     * Application Construct.
     */
    public function __construct(array $config = [])
    {
        $this->runtime = RuntimeFactory::createInstance($config);
        $this->router = new Router;

        // 注册请求处理回调 handle
        $this->handleRequest = $this->defaultRequestHandle();
        // 注册应用抛出异常时处理 handle
        $this->handleThrow = [Handler::class, 'reportAndprepareResponse'];
    }

    /**
     * 动态代理 router，路由规则注册
     */
    public function __call(string $name, $arguments)
    {
        call_user_func_array([$this->router, $name], $arguments);
    }

    /**
     * 注册中间件
     *
     * @param callable $middleware
     * @return static
     */
    public function use($middleware)
    {
        if (! (new $middleware) instanceof MiddlewareInterface && ! is_callable($middleware)) {
            throw new InvalidArgumentException(
                '中间件必须为闭包或者 MiddlewareInterface 子类'
            );
        }

        $this->middlewares[] = $middleware;
    }

    /**
     * 监听指定端口并启动 HTTP 服务
     */
    public function listen(int $port = 9527, string $host = '127.0.0.1')
    {
        $this->registerBuiltInMiddleware();

        // 注册并启动服务
        $this
            ->runtime
            ->withRequestHanle($this->handleRequest)
            ->start($port, $host)
        ;
    }

    public function set(string $event, $handle)
    {
        switch ($event) {
            case 'handleRequest':
                $this->handleRequest = $handle;
                break;

            case 'handleThrow':
                $this->handleThrow = $handle;
                break;

            case 'handleNotFound':
                $this->router->notFound($handle);
                break;

        }
    }

    protected function defaultRequestHandle(): callable
    {
        return function (Context $ctx) {
            try {
                $ctx->route = $this->router->match(
                    $ctx->request->getMethod(),
                    $ctx->request->getUri()->getPath()
                );

                return (new Pipeline)
                    ->send($ctx)
                    ->throgh($this->middlewares)
                    ->then(function ($ctx) {
                        return $ctx->response;
                    })
                ;
            } catch (Throwable $e) {
                return call_user_func($this->handleThrow, $e);
            }
        };
    }

    /**
     * 注册内置中间件
     *
     * 该方法在所有自定义中间件注册完毕
     * 服务启动之前进行执行注册
     */
    private function registerBuiltInMiddleware(): void
    {
        $this->use(\Hi\Http\Middleware\DispatchMiddleware::class);
    }
}

