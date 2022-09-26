<?php

namespace Hi\Http;

use Hi\Http\Exceptions\Handler;
use Hi\Http\Middleware\MiddlewareInterface;
use Hi\Http\Runtime\Bridge;
use Hi\Http\Runtime\Factory as RuntimeFactory;
use InvalidArgumentException;
use Throwable;

/**
 * @method \Hi\Http\Router get(string $pattern, callable $handle)
 * @method \Hi\Http\Router post(string $pattern, callable $handle)
 * @method \Hi\Http\Router put(string $pattern, callable $handle)
 * @method \Hi\Http\Router delete(string $pattern, callable $handle)
 * @method \Hi\Http\Router head(string $pattern, callable $handle)
 * @method \Hi\Http\Router options(string $pattern, callable $handle)
 * @method \Hi\Http\Router patch(string $pattern, callable $handle)
 * @method \Hi\Http\Router group(array $config)
 */
class Application
{
    /**
     * @var Bridge
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
     * 全局异常 handle
     *
     * @var callable
     */
    protected $throwHandle;

    protected $basePath;

    /**
     * Application Construct.
     */
    public function __construct(array $config = [])
    {
        $this->runtime       = RuntimeFactory::create($config);
        $this->router        = new Router();
        $this->handleThrow   = [Handler::class, 'prepareResponse'];

        $this->runtime->withRequestHandle($this->defaultRequestHandle());
    }

    /**
     * 动态代理 router，路由规则注册
     */
    public function __call(string $name, $arguments): Application
    {
        call_user_func_array([$this->router, $name], $arguments);

        return $this;
    }

    /**
     * 注册中间件
     *
     * @param callable|MiddlewareInterface $middleware
     */
    public function use($middleware): Application
    {
        if (!is_callable($middleware) && !(new $middleware()) instanceof MiddlewareInterface) {
            throw new InvalidArgumentException(
                '中间件必须为闭包或者 MiddlewareInterface 子类'
            );
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    /**
     * 批量注册中间件
     */
    public function uses(array $middlewares): Application
    {
        foreach ($middlewares as $middleware) {
            $this->use($middleware);
        }

        return $this;
    }

    public function setBasePath(string $path): Application
    {
        $this->basePath = $path;

        return $this;
    }

    public function setNotFoundHandle(callable $handle): Application
    {
        $this->router->setNotFoundHandle($handle);

        return $this;
    }

    public function setThrowHandle(callable $handle): Application
    {
        $this->throwHandle = $handle;

        return $this;
    }

    public function setRequestHadle(callable $handle): Application
    {
        $this->runtime->withRequestHandle($handle);

        return $this;
    }

    public function loadRoutes(array $files): Application
    {
        extract(['router' => $this->router]);

        foreach ($files as $file) {
            require $this->basePath . $file;
        }

        return $this;
    }

    /**
     * 返回默认请求事件处理回调
     */
    protected function defaultRequestHandle(): callable
    {
        return function (Context $ctx) {
            try {
                $ctx->route = $this->router->match(
                    $ctx->request->getMethod(),
                    $ctx->request->getUri()->getPath()
                );

                return (new Pipeline())
                    ->send($ctx)
                    ->throgh($this->middlewares)
                    ->then(function (Context $ctx) {
                        return $ctx->response;
                    });
            } catch (Throwable $e) {
                return call_user_func($this->handleThrow, $e, $ctx->request, $ctx->response);
            }
        };
    }

    /**
     * 注册内置中间件
     *
     * 该方法在所有自定义中间件注册完毕
     * 服务启动之前进行执行注册
     */
    protected function registerBuiltInMiddleware(): void
    {
        if (!$this->middlewares) {
            $this->use(\Hi\Http\Middleware\Dispatch::class);
        }
    }

    /**
     * 返回当前对应所使用 Router 实例
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * 返回运行时容器实例
     */
    public function getRuntime(): Bridge
    {
        return $this->runtime;
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
            ->withRequestHandle($this->handleRequest)
            ->withHost($host)
            ->withPort($port)
            ->start()
        ;
    }
}