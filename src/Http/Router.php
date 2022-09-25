<?php

declare(strict_types=1);

namespace Hi\Http;

use Closure;
use Hi\Http\Router\Route;
use Hi\Http\Router\RouterInterface;
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
     * @var array
     */
    protected $extend = [];

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
     * @return $this
     */
    public function get(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('GET', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 POST 请求路由规则
     *
     * @return $this
     */
    public function post(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('POST', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 PUT 请求路由规则
     *
     * @return $this
     */
    public function put(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('PUT', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 DELETE 请求路由规则
     *
     * @return $this
     */
    public function delete(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('DELETE', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 HEAD 请求路由规则
     *
     * @return $this
     */
    public function head(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('HEAD', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 OPTIONS 请求路由规则
     *
     * @return $this
     */
    public function options(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('OPTIONS', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 注册 PATCH 请求路由规则
     *
     * @return $this
     */
    public function patch(string $pattern, Closure $handle, array $extend = [])
    {
        $this->mount('PATCH', $pattern, $handle, $extend);

        return $this;
    }

    /**
     * 以组方式注册路由规则
     *
     * @return $this
     */
    public function group(string $prefix, Closure $handle, array $extend = [])
    {
        $this->prefix = '/' . trim($prefix, '/');
        $this->extend = $extend;

        call_user_func($handle, $this);

        $this->prefix = '';
        $this->extend = [];

        return $this;
    }

    /**
     * 挂载（注册）路由至路由树
     */
    public function mount(string $method, string $pattern, Closure $handle, array $extend = [])
    {
        $pattern               = $this->prefix . '/' . trim($pattern, '/');
        $routeKey              = $this->routeKey($method, $pattern);
        $this->tree[$routeKey] = [$handle, array_merge($this->extend, $extend)];

        return $this;
    }

    /**
     * 匹配指定路由路径
     */
    public function match(string $method, string $pattern): Route
    {
        $routeKey = $this->routeKey($method, $pattern);

        return isset($this->tree[$routeKey])
            ? new Route(
                true,
                $method,
                $pattern,
                $this->tree[$routeKey][0],
                $this->tree[$routeKey][1]
            )
            : new Route(
                false,
                $method,
                $pattern,
                $this->notFoundHandle
            );
    }

    /**
     * 拼接路由键
     */
    protected function routeKey($method, $pattern): string
    {
        return $method . $pattern;
    }

    /**
     * 注册未匹配到路由路径时回调方法
     * 即处理发生 404 的情况
     */
    public function setNotFoundHandle($handle)
    {
        if (!is_callable($handle)) {
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
            $ctx->response = $ctx->response->withStatus(404);

            return 'resource not found';
        };
    }
}
