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
     * GET 方法路由注
     *
     * @param callable  $handle
     * @param mixed     $extend
     * @return $this
     */
    public function get(string $pattern, $handle, $extend = null)
    {
        $this->mount('GET', $pattern, $handle, $extend);
    }

    public function post(string $pattern, $handle, $extend = null)
    {
        $this->mount('POST', $pattern, $handle, $extend);
    }

    public function put(string $pattern, $handle, $extend = null)
    {
        $this->mount('PUT', $pattern, $handle, $extend);
    }

    public function delete(string $pattern, $handle, $extend = null)
    {
        $this->mount('DELETE', $pattern, $handle, $extend);
    }

    public function head(string $pattern, $handle, $extend = null)
    {
        $this->mount('HEAD', $pattern, $handle, $extend);
    }

    public function options(string $pattern, $handle, $extend = null)
    {
        $this->mount('OPTIONS', $pattern, $handle, $extend);
    }

    public function patch(string $pattern, $handle, $extend = null)
    {
        $this->mount('PATCH', $pattern, $handle, $extend);
    }

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
    }

    public function mount(string $method, string $pattern, $handle, $extend = null)
    {
        $pattern = $this->prefix . '/' . trim($pattern, '/');
        $this->tree[$method . $pattern] = [$handle, $extend];
    }

    public function match(string $method, string $pattern): Route
    {
        $key = $method . $pattern;
        return isset($this->tree[$key]) 
            ? new Route(true, $method, $pattern, $this->tree[$key][0], $this->tree[$key][1]) 
            : new Route(false, $method, $pattern, $this->notFoundHandle);
    }

    public function notFound($handle)
    {
        if (! is_callable($handle)) {
            throw new InvalidArgumentException('notFound handle 必须为 callable 类型');
        }

        $this->notFoundHandle = $handle;
    }

    protected function defaultNotFoundHandle()
    {
        return function (Context $ctx) {
            $ctx->assert(false, 404, 'resource not found!');
        };
    }
}
