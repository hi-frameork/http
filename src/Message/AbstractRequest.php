<?php declare(strict_types=1);

namespace Hi\Http\Message;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function is_string;
use function is_object;
use function is_null;
use function preg_match;

class AbstractRequest extends AbstractMessage
{
    /**
     * @var string
     */
    protected $method = 'GET';

    /**
     * @var null|string
     */
    protected $requestTarget;

    /**
     * 获取消息的请求目标。
     * 
     * 获取消息的请求目标的使用场景，可能是在客户端，也可能是在服务器端，也可能是在指定信息的时候
     * （参阅下方的 `withRequestTarget()`）。
     * 
     * 在大部分情况下，此方法会返回组合 URI 的原始形式，除非被指定过（参阅下方的 `withRequestTarget()`）。
     *
     * 如果没有可用的 URI，并且没有设置过请求目标，此方法 **必须** 返回 「/」。
     *
     * @return string
     */
    public function getRequestTarget()
    {
        $requestTarget = $this->requestTarget;

        if (null === $requestTarget) {
            $requestTarget = $this->uri->getPath();

            if ($this->uri->getQuery()) {
                $requestTarget .= '?' . $this->uri->getQuery();
            }

            if (! $requestTarget) {
                $requestTarget = '/';
            }
        }

        return $requestTarget;
    }

    /**
     * 返回一个指定目标的请求实例。
     * 
     * 如果请求需要非原始形式的请求目标——例如指定绝对形式、认证形式或星号形式——则此方法
     * 可用于创建指定请求目标的实例。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @see [http://tools.ietf.org/html/rfc7230#section-2.7](http://tools.ietf.org/html/rfc7230#section-2.7) 
     * （关于请求目标的各种允许的格式）
     * 
     * @param mixed $requestTarget
     * @return self
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match("/\s/", $requestTarget)) {
            throw new InvalidArgumentException('request target 不能含有空格字符');
        }

        return $this->cloneInstance($requestTarget, "requestTarget");
    }

    /**
     * 获取当前请求使用的 HTTP 方法
     *
     * @return string HTTP 方法字符串
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * 返回更改了请求方法的消息实例。
     *
     * 虽然，在大部分情况下，HTTP 请求方法都是使用大写字母来标示的，但是，实现类库 **不应该**
     * 修改用户传参的大小格式。
     * 
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @param string $method 大小写敏感的方法名
     * @return self
     * @throws \InvalidArgumentException 当非法的 HTTP 方法名传入时会抛出异常。
     */
    public function withMethod($method)
    {
        $this->processMethod($method);
        return $this->cloneInstance($method, 'method');
    }

    /**
     * 获取 URI 实例。
     *
     * 此方法 **必须** 返回 `UriInterface` 的 URI 实例。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     */
    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    /**
     * 返回修改了 URI 的消息实例。
     *
     * 当传入的 URI 包含有 HOST 信息时，此方法 **必须** 更新 HOST 信息。如果 URI 
     * 实例没有附带 HOST 信息，任何之前存在的 HOST 信息 **必须** 作为候补，应用
     * 更改到返回的消息实例里。
     * 
     * 你可以通过传入第二个参数来，来干预方法的处理，当 `$preserveHost` 设置为 `true` 
     * 的时候，会保留原来的 HOST 信息。当 `$preserveHost` 设置为 `true` 时，此方法
     * 会如下处理 HOST 信息：
     * 
     * - 如果 HOST 信息不存在或为空，并且新 URI 包含 HOST 信息，则此方法 **必须** 更新返回请求中的 HOST 信息。
     * - 如果 HOST 信息不存在或为空，并且新 URI 不包含 HOST 信息，则此方法 **不得** 更新返回请求中的 HOST 信息。
     * - 如果HOST 信息存在且不为空，则此方法 **不得** 更新返回请求中的 HOST 信息。
     * 
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 请求实例，然后返回
     * 一个新的修改过的 HTTP 请求实例。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     * @param UriInterface $uri `UriInterface` 新的 URI 实例
     * @param bool $preserveHost 是否保留原有的 HOST 头信息
     * @return self
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $preserveHost = (bool) $preserveHost;
        $headers      = clone $this->headers;
        $newInstance  = $this->cloneInstance($uri, 'uri');

        if (! $preserveHost) {
            $newInstance->headers = $this->checkHeaderHost($headers);
        }

        return $newInstance;
    }

    /**
     * 检查 method 是否有效
     */
    final protected function processMethod(string $method = ""): string
    {
        $methods = [
            "GET"     => 1,
            "CONNECT" => 1,
            "DELETE"  => 1,
            "HEAD"    => 1,
            "OPTIONS" => 1,
            "PATCH"   => 1,
            "POST"    => 1,
            "PUT"     => 1,
            "TRACE"   => 1
        ];

        if (! $method || ! isset($methods[$method])) {
            throw new InvalidArgumentException($method . ' 不被支持');
        }

        return $method;
    }

    /**
     * 返回处理好 UriInterface 实例
     */
    final protected function processUri($uri): UriInterface
    {
        if (is_object($uri) && $uri instanceof UriInterface) {
            return $uri;
        }

        if (is_string($uri)) {
            return new Uri($uri);
        }

        if (is_null($uri)) {
            return new Uri();
        }

        throw new InvalidArgumentException('uri 参数无效');
    }
}
