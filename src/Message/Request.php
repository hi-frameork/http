<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Http\Message\Stream\Input;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * 代表客户端向服务器发起请求的 HTTP 消息对象。
 *
 * 根据 HTTP 规范，此接口包含以下属性：
 *
 * - HTTP 协议版本号
 * - HTTP 请求方法
 * - URI
 * - 报头信息
 * - 消息内容
 *
 * 在构造 HTTP 请求对象的时候，如果没有提供 Host 信息，
 * 实现类库 **必须** 从给出的 URI 中去提取 Host 信息。
 *
 * HTTP 请求是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的新的 HTTP 请求实例返回。
 */
final class Request extends AbstractRequest implements RequestInterface
{
    /**
     * Request constructor.
     *
     * @param string                          $method
     * @param UriInterface|string|null        $uri
     * @param StreamInterface|resource|string $body
     * @param array                           $headers
     */
    public function __construct(
        string $method = 'GET',
        $uri = null,
        $body = 'php://memory',
        $headers = []
    ) {
        if ('php://input' === $body) {
            $body = new Input;
        }

        $this->uri     = $this->processUri($uri);
        $this->headers = $this->processHeaders($headers);
        $this->method  = $this->processMethod($method);
        $this->body    = $this->processBody($body);
    }
}
