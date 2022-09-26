<?php

declare(strict_types=1);

namespace Hi\Http\Router;

class Route
{
    /**
     * @var bool
     */
    protected $match = false;

    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var string
     */
    protected $pattern = '';

    /**
     * @var callable
     */
    protected $handle;

    /**
     * @var mixed
     */
    protected $extend;

    /**
     * Route Construct
     *
     * @param callable $handle
     */
    public function __construct(
        bool $match = false,
        string $method = 'GET',
        string $pattern,
        $handle,
        $extend = null
    ) {
        $this->match   = $match;
        $this->method  = $method;
        $this->pattern = $pattern;
        $this->handle  = $handle;
        $this->extend  = $extend;
    }

    /**
     * 返回请求是否存在读应路由
     */
    public function isMatch(): bool
    {
        return $this->match;
    }

    /**
     * 返回请求的 method
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * 返回匹配的路径
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * 返回请求路径所绑定处理器
     *
     * @return callable
     */
    public function getHandle()
    {
        return $this->handle;
    }

    /**
     * 返回路由扩展信息
     *
     * 在路由绑定时注册的额外信息
     * 可用于中间件中自定义条件处理等场景
     *
     * @return mixed
     */
    public function getExtend()
    {
        return $this->extend;
    }

    /**
     * 调用当前路由绑定的执行器
     *
     * @return mixed
     */
    public function call()
    {
        return call_user_func_array($this->handle, func_get_args());
    }
}
