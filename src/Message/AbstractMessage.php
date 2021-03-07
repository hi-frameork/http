<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Collection;
use Hi\Helpers\Collection\CollectionInterface;
use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * 
 * HTTP 消息包括客户端向服务器发起的「请求」和服务器端返回给客户端的「响应」。
 * 此接口定义了他们通用的方法。
 * 
 * HTTP 消息是被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套
 * 机制，在内部保持好原有的内容，然后把修改状态后的信息返回。
 *
 * @see http://www.ietf.org/rfc/rfc7230.txt
 * @see http://www.ietf.org/rfc/rfc7231.txt
 */
class AbstractMessage extends AbstractCommon implements MessageInterface
{
    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var CollectionInterface<string, mixed>
     */
    protected $headers;

    /**
     * HTTP 协议版本
     * 例如，“1.1”、“1.0”
     *
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * @see http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @var UriInterface
     */
    protected $uri;

    /**
     * 获取字符串形式的 HTTP 协议版本信息。
     *
     * 字符串 **必须** 包含 HTTP 版本数字（如：「1.1」, 「1.0」）。
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * 返回指定 HTTP 版本号的消息实例。
     *
     * 传参的版本号只 **必须** 包含 HTTP 版本数字，如："1.1", "1.0"。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的带有传参进去的 HTTP 版本的实例
     *
     * @param string $version HTTP 版本信息
     * @return static
     */
    public function withProtocolVersion($version)
    {
        $this->processProtocol($version);
        return $this->cloneInstance($version, 'protocolVersion');
    }

    /**
     * 获取所有的报头信息
     *
     * 返回的二维数组中，第一维数组的「键」代表单条报头信息的名字，「值」是
     * 以数组形式返回的，见以下实例：
     *
     *     // 把「值」的数据当成字串打印出来
     *     foreach ($message->getHeaders() as $name => $values) {
     *         echo $name . ': ' . implode(', ', $values);
     *     }
     *
     *     // 迭代的循环二维数组
     *     foreach ($message->getHeaders() as $name => $values) {
     *         foreach ($values as $value) {
     *             header(sprintf('%s: %s', $name, $value), false);
     *         }
     *     }
     *
     * 虽然报头信息是没有大小写之分，但是使用 `getHeaders()` 会返回保留了原本
     * 大小写形式的内容。
     *
     * @return string[][] 返回一个两维数组，第一维数组的「键」 **必须** 为单条报头信息的
     *     名称，对应的是由字串组成的数组，请注意，对应的「值」 **必须** 是数组形式的。
     */
    public function getHeaders()
    {
        return $this->headers->toArray();
    }

    /**
     * 检查是否报头信息中包含有此名称的值，不区分大小写
     *
     * @param string $name 不区分大小写的报头信息名称
     */
    public function hasHeader($name): bool
    {
        return $this->headers->has($name);
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以数组形式返回
     *
     * 此方法以数组形式返回对应名称的报头信息。
     *
     * 如果没有对应的报头信息，**必须** 返回一个空数组。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @return string[] 返回报头信息中，对应名称的，由字符串组成的数组值，如果没有对应
     *     的内容，**必须** 返回空数组。
     */
    public function getHeader($name): array
    {
        return $this->headers->get((string) $name, []);
    }

    /**
     * 根据给定的名称，获取一条报头信息，不区分大小写，以逗号分隔的形式返回
     * 
     * 此方法返回所有对应的报头信息，并将其使用逗号分隔的方法拼接起来。
     *
     * 注意：不是所有的报头信息都可使用逗号分隔的方法来拼接，对于那些报头信息，请使用
     * `getHeader()` 方法来获取。
     * 
     * 如果没有对应的报头信息，此方法 **必须** 返回一个空字符串。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @return string 返回报头信息中，对应名称的，由逗号分隔组成的字串，如果没有对应
     *     的内容，**必须** 返回空字符串。
     */
    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    /**
     * 返回替换指定报头信息「键/值」对的消息实例。
     *
     * 虽然报头信息是不区分大小写的，但是此方法必须保留其传参时的大小写状态，并能够在
     * 调用 `getHeaders()` 的时候被取出。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个更新后带有传参进去报头信息的实例
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     * @return static
     * @throws \InvalidArgumentException 无效的报头字段或报头信息时抛出
     */
    public function withHeader($name, $value)
    {
        $this->checkHeaderName($name);

        $headers = clone $this->headers;
        $value   = $this->getHeaderValue($value);

        $headers->set($name, $value);

        return $this->cloneInstance($headers, 'headers');
    }

    /**
     * 返回一个报头信息增量的 HTTP 消息实例。
     *
     * 原有的报头信息会被保留，新的值会作为增量加上，如果报头信息不存在的话，字段会被加上。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @param string $name 不区分大小写的报头字段名称。
     * @param string|string[] $value 报头信息或报头信息数组。
     * @return static
     * @throws \InvalidArgumentException 报头字段名称非法时会被抛出。
     * @throws \InvalidArgumentException 报头头信息的值非法的时候会被抛出。
     */
    public function withAddedHeader($name, $value)
    {
        $this->checkHeaderName($name);

        $headers  = clone $this->headers;
        $existing = $headers->get($name, []);
        $value    = $this->getHeaderValue($value);
        $value    = array_merge($existing, $value);

        $headers->set($name, $value);

        return $this->cloneInstance($headers, "headers");
    }

    /**
     * 返回被移除掉指定报头信息的 HTTP 消息实例。
     *
     * 报头信息字段在解析的时候，**必须** 保证是不区分大小写的。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @param string $name 不区分大小写的头部字段名称。
     * @return static
     */
    public function withoutHeader($name)
    {
        $headers = clone $this->headers;
        $headers->remove($name);

        return $this->cloneInstance($headers, 'headers');
    }

    /**
    * 获取 HTTP 消息的内容。
    */
    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    /**
     * 返回指定内容的 HTTP 消息实例。
     *
     * 内容 **必须** 是 `StreamInterface` 接口的实例。
     *
     * 此方法在实现的时候，**必须** 保留原有的不可修改的 HTTP 消息对象，然后返回
     * 一个新的修改过的 HTTP 消息实例。
     *
     * @return static
     * @throws \InvalidArgumentException 当消息内容不正确的时候抛出。
     */
    public function withBody(StreamInterface $body)
    {
        $newBody = $this->processBody($body, 'w+b');

        return $this->cloneInstance($newBody, 'body');
    }

    /**
     * 检查 header 名称，如果无效将会抛出异常
     *
     * @see http://tools.ietf.org/html/rfc7230#section-3.2
     */
    final protected function checkHeaderName(string $name): void
    {
        if (! is_string($name) || !preg_match("/^[a-zA-Z0-9'`#$%&*+.^_|~!-]+$/", $name)) {
            throw new InvalidArgumentException("无效的 header 名称 " . $name);
        }
    }

    /**
     * @param mixed $value
     */
    final protected function checkHeaderValue($value): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException('Header 值无效');
        }

        $value = (string) $value;

        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value) ||
            preg_match("/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/", $value)) {
            throw new InvalidArgumentException('Header 值无效');
        }
    }

    /**
     * 检查 header 值，如果为有效值返回该值
     * 否则抛出异常
     *
     * @param int|string|array<int|string> $values
     * @return array<int|string>
     */
    final protected function getHeaderValue($values): array
    {
        $valueArray = $values;
        if (! is_array($values)) {
            $valueArray = [$values];
        }

        if (empty($valueArray)) {
            throw new InvalidArgumentException('Heaer 值无效：必须为 string 或 array<string>');
        }

        $valueData = [];
        foreach ($valueArray as $value) {
            $this->checkHeaderValue($value);
            $valueData[] = $value;
        }

        return $valueData;
    }

    /**
     * Set a valid stream
     *
     * @param StreamInterface|resource|string $body
     *
     * @return StreamInterface
     */
    final protected function processBody($body = "php://memory", string $mode = "r+b"): StreamInterface
    {
        if (is_object($body) && $body instanceof StreamInterface) {
            return $body;
        }

        if (! is_string($body) && ! is_resource($body)) {
            throw new InvalidArgumentException("Invalid stream passed as a parameter");
        }

        return new Stream($body, $mode);
    }

    /**
     * 检查所支持的协议版本
     */
    final protected function processProtocol(string $protocol = ""): string
    {
        $protocols = [
            "1.0" => 1,
            "1.1" => 1,
            "2.0" => 1,
            "3.0" => 1,
        ];

        if (empty($protocol) || ! is_string($protocol)) {
            throw new InvalidArgumentException('协议版本号无效');
        }

        if (!isset($protocols[$protocol])) {
            throw new InvalidArgumentException('Unsupported protocol ' . $protocol);
        }

        return $protocol;
    }

    /**
     * 返回主机和端口（如果适用）
     */
    final protected function getUriHost(UriInterface $uri): string
    {
        $host = $uri->getHost();

        if (null != $uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        return $host;
    }

    /**
     * Ensure Host is the first header.
     *
     * @see: http://tools.ietf.org/html/rfc7230#section-5.4
     */
    final protected function checkHeaderHost(CollectionInterface $collection): CollectionInterface
    {
        if ($collection->has('host') && ! empty($this->uri) && '' !== $this->uri->getHost()) {

            $host      = $this->getUriHost($this->uri);
            $hostArray = $host;

            if (! is_array($hostArray)) {
                $hostArray = [$host];
            }

            $collection->remove('host');

            $data           = $collection->toArray();
            $header         = [];
            $header["Host"] = $hostArray;
            $header         = $header + (array) $data;

            $collection->clear();
            $collection->init($header);
        }

        return $collection;
    }

    /**
     * 处理 HTTP header 信息
     */
    final protected function processHeaders($headers): CollectionInterface
    {
        if (is_array($headers)) {
            $collection = $this->populateHeaderCollection($headers);
            $collection = $this->checkHeaderHost($collection);
        } else {
            if (! (is_object($headers) && $headers instanceof CollectionInterface)) {
                throw new InvalidArgumentException(
                    'Headers 值类型必须为 array 或者 Hi\Helpers\Collection 类实例'
                );
            }

            $collection = $headers;
        }

        return $collection;
    }

    /**
     * Populates the header collection
     *
     * @param array $headers
     *
     * @return CollectionInterface
     */
    final protected function populateHeaderCollection(array $headers): CollectionInterface
    {
        $collection = new Collection();
        foreach ($headers as $name => $value) {
            $this->checkHeaderName($name);

            $name  = (string) $name;
            $value = $this->getHeaderValue($value);

            $collection->set($name, $value);
        }

        return $collection;
    }
}
