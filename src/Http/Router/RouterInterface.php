<?php

declare(strict_types=1);

namespace Hi\Http\Router;

use Closure;

interface RouterInterface
{
    /**
     * 注册 GET 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function get(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 POST 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function post(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 PUT 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function put(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 DELETE 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function delete(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 HEAD 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function head(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 GET 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function options(string $pattern, Closure $handle, array $extend = []);

    /**
     * 注册 PATCH 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function patch(string $pattern, Closure $handle, array $extend = []);

    /**
     * 以组方式注册路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function group(string $prefix, Closure $handle, array $extend = []);

    /**
     * 将 HTTP 方法挂载到路由树
     *
     * @param callable  $handle
     * @param mixed     $extend
     * @return $this
     */
    public function mount(string $method, string $pattern, Closure $handle, array $extend = []);

    /**
     * 根据传入方法与路径在路由树上查找对应 handle
     */
    public function match(string $method, string $pattern): Route;

    /**
     * 注册路由未有匹配 handle 时回调
     *
     * @param callable $handle
     */
    public function notFound($handle);
}
