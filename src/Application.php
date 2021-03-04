<?php declare(strict_types=1);

namespace Hi\Http;

use Hi\Http\Runtime\RuntimeFactory;
use Hi\Pipeline\PipelineFactory;
use Hi\Pipeline\PipelineInterface;

/**
 * @method get(string $pattern, callable $handle)
 * @method post(string $pattern, callable $handle)
 * @method put $pattern, callable $handle)
 * @method delete(string $pattern, callable $handle)
 * @method head(string $pattern, callable $handle)
 * @method options(string $pattern, callable $handle)
 * @method patch(string $pattern, callable $handle)
 * @method notFound(callable $handle)
 */
class Application
{
    /**
     * @var \Hi\Server\AbstructServer
     */
    protected $runtime;

    /**
     * @var PipelineInterface
     */
    protected $pipeline;

    protected $router;

    /**
     * @var callable
     */
    protected $errorHandler;

    /**
     * Application Construct.
     */
    public function __construct(array $config = [])
    {
        $this->runtime  = RuntimeFactory::createInstance($config);
        $this->pipeline = PipelineFactory::createReducePipeline();
    }

    public function __call($name, $arguments)
    {
        $this->router->{$name}($arguments[0], $arguments[1]);
    }

    /**
     * 注册中间件
     *
     * @param callable $middleware
     * @return static
     */
    public function use($middleware)
    {
        $this->pipeline->appendStage($middleware);
    }

    /**
     * 监听指定端口并启动 HTTP 服务
     */
    public function listen(int $port = null, string $host = null): void
    {
        // 注册并启动服务
        $this
            ->runtime
            ->withRequestHanle($this->callback())
            ->start($port, $host)
        ;
    }

    /**
     * 返回请求处理回调
     */
    public function callback(): callable
    {
        return function (Context $ctx) {
            $this->pipeline->process($ctx, function ($ctx) {
            });
        };
    }

    /**
     * 注册异常发生时异常处理回调方法
     */
    public function error(callable $errorHandler): void
    {
        $this->errorHandler = $errorHandler;
    }
}

