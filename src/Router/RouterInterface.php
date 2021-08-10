<?php declare(strict_types=1);

namespace Hi\Http\Router;

interface RouterInterface
{
    /**
     * 注册 GET 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function get(string $pattern, $handle, $extend = null);

    /**
     * 注册 POST 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function post(string $pattern, $handle, $extend = null);

    /**
     * 注册 PUT 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function put(string $pattern, $handle, $extend = null);

    /**
     * 注册 DELETE 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function delete(string $pattern, $handle, $extend = null);

    /**
     * 注册 HEAD 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function head(string $pattern, $handle, $extend = null);

    /**
     * 注册 GET 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function options(string $pattern, $handle, $extend = null);

    /**
     * 注册 PATCH 请求路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function patch(string $pattern, $handle, $extend = null);

    /**
     * 以组方式注册路由规则
     *
     * @param mixed $extend
     * @return $this
     */
    public function group(string $prefix, $handle, $extend = null);

    /**
     * 将 HTTP 方法挂载到路由树
     *
     * @param callable  $handle
     * @param mixed     $extend
     * @return $this
     */
    public function mount(string $method, string $pattern, $handle, $extend = null);

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
