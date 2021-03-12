<?php declare(strict_types=1);

namespace Hi\Http;

use Hi\Http\Router\RouterInterface;
use Hi\Http\Router\Route;
use InvalidArgumentException;

class Router implements RouterInterface
{
    /**
     * @var array
     */
    protected $tree = [];

    /**
     * @var callable
     */
    protected $notFoundHandle;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * Router Construct
     */
    public function __construct()
    {
        $this->notFoundHandle = $this->defaultNotFoundHandle();
    }

    /**
     * 注册 GET 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function get(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('GET', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 POST 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function post(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('POST', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 PUT 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function put(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('PUT', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 DELETE 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function delete(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('DELETE', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 HEAD 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function head(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('HEAD', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 OPTIONS 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function options(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('OPTIONS', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 注册 PATCH 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function patch(string $pattern, callable $handle, $extend = null)
    {
        $this->mount('PATCH', $pattern, $handle, $extend);
        return $this;
    }

    /**
     * 以组方式注册路由规则
     *
     * @return $this
     */
    public function group(array $configs)
    {
        if (empty($configs)) {
            return;
        }

        foreach ($configs as $prefix => $item) {
            $this->prefix = '/' . trim($prefix, '/');
            $item($this);
            $this->prefix = '';
        }

        return $this;
    }

    /**
     * 挂载（注册）路由至路由树
     */
    public function mount(string $method, string $pattern, $handle, $extend = null)
    {
        $pattern = $this->prefix . '/' . trim($pattern, '/');
        $this->tree[$method . $pattern] = [$handle, $extend];
    }

    /**
     * 匹配指定路由路径
     */
    public function match(string $method, string $pattern): Route
    {
        $key = $method . $pattern;
        return isset($this->tree[$key]) 
            ? new Route(true, $method, $pattern, $this->tree[$key][0], $this->tree[$key][1]) 
            : new Route(false, $method, $pattern, $this->notFoundHandle);
    }

    /**
     * 注册未匹配到路由路径时回调方法
     * 即处理发生 404 的情况
     */
    public function notFound($handle)
    {
        if (! is_callable($handle)) {
            throw new InvalidArgumentException('notFound handle 必须为 callable 类型');
        }

        $this->notFoundHandle = $handle;
    }

    /**
     * 默认 notFound 回调方法
     *
     * @return \Closure
     */
    protected function defaultNotFoundHandle()
    {
        return function (Context $ctx) {
            $ctx->assert(false, 404, 'resource not found!');
        };
    }
}
