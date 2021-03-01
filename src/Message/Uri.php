<?php declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Arr;
use InvalidArgumentException;
use Psr\Http\Message\UriInterface;

use function is_string;
use function parse_url;
use function strtolower;
use function rawurlencode;
use function strpos;
use function explode;
use function implode;
use function ltrim;
use function preg_replace;

/**
 * URI 数据对象。
 *
 * 此接口按照 RFC 3986 来构建 HTTP URI，提供了一些通用的操作，你可以自由的对此接口
 * 进行扩展。你可以使用此 URI 接口来做 HTTP 相关的操作，也可以使用此接口做任何 URI 
 * 相关的操作。
 *
 * 此接口的实例化对象被视为无法修改的，所有能修改状态的方法，都 **必须** 有一套机制，在内部保
 * 持好原有的内容，然后把修改状态后的，新的实例返回。
 *
 * 通常，HOST 信息也将出现在请求消息中。对于服务器端的请求，通常可以在服务器参数中发现此信息。
 * 
 * @see [URI 通用标准规范](http://tools.ietf.org/html/rfc3986)
 *
 * URI 结构：
 *  {scheme://{user}:{$pass}@{host}{path}{fragment}}
 */
final class Uri extends AbstractCommon implements UriInterface
{
    /**
     * @var string
     */
    protected $scheme = 'https';

    /**
     * URI 包含片段数据
     *
     * 例如：
     *  http://example.com/user#profile
     * 其中 #profile 即为 fragment
     *
     * @var string
     */
    protected $fragment = '';

    /**
     * URI 携带域名
     *
     * @var string
     */
    protected $host = '';

    /**
     * URL 携带认证信息：用户名
     *
     * @var string
     */
    protected $user = '';

    /**
     * URL 携带认证信息：密码
     *
     * @var string
     */
    protected $pass = '';

    /**
     * URI 携带路径
     *
     * @var string
     */
    protected $path = '';

    /**
     * URI 携带端口
     *
     * @var int|null
     */
    protected $port = null;

    /**
     * @var string
     */
    protected $query = '';

    /**
     * Uri constructor.
     */
    public function __construct(string $uri = '')
    {
        if (is_string($uri)) {
            $urlParts = parse_url($uri);

            if (false === $urlParts) {
                $urlParts = [];
            }

            $this->fragment = $this->filterFragment(Arr::get($urlParts, 'fragment', ''));
            $this->host     = strtolower(Arr::get($urlParts, 'host', ''));
            $this->pass     = rawurlencode(Arr::get($urlParts, 'pass', ''));
            $this->path     = $this->filterPath(Arr::get($urlParts, 'path', ''));
            $this->port     = $this->filterPort(Arr::get($urlParts, 'port', null));
            $this->query    = $this->filterQuery(Arr::get($urlParts, 'query', ''));
            $this->scheme   = $this->filterScheme(Arr::get($urlParts, 'scheme', ''));
            $this->user     = rawurlencode(Arr::get($urlParts, "user", ""));
        }
    }

     /**
     * 返回字符串表示形式的 URI。
     *
     * 根据 RFC 3986 第 4.1 节，结果字符串是完整的 URI 还是相对引用，取决于 URI 有哪些组件。
     * 该方法使用适当的分隔符连接 URI 的各个组件：
     *
     * - 如果存在 Scheme 则 **必须** 以「:」为后缀。
     * - 如果存在认证信息，则必须以「//」作为前缀。
     * - 路径可以在没有分隔符的情况下连接。但是有两种情况需要调整路径以使 URI 引用有效，因为 PHP
     *   不允许在 `__toString()` 中引发异常：
     *     - 如果路径是相对的并且有认证信息，则路径 **必须** 以「/」为前缀。
     *     - 如果路径以多个「/」开头并且没有认证信息，则起始斜线 **必须** 为一个。
     * - 如果存在查询字符串，则 **必须** 以「?」作为前缀。
     * - 如果存在片段（Fragment），则 **必须** 以「#」作为前缀。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-4.1
     */
    public function __toString(): string
    {
        $authority = $this->getAuthority();
        $path      = $this->path;

        if ('' !== $path& '/' !== $path[0] && '' !== $authority) {
            $path = '/' . $path;
        }

        $url = $this->checkValue($this->scheme, '', ':')
             . $this->checkValue($authority, '//')
             . $path
             . $this->checkValue($this->query, '?')
             . $this->checkValue($this->fragment, '#')
        ;

        return $url;
    }

    /**
     * 返回 URI 认证信息。
     *
     * 如果没有 URI 认证信息的话，**必须** 返回一个空字符串。
     *
     * URI 的认证信息语法是：
     *
     * <pre>
     * [user-info@]host[:port]
     * </pre>
     *
     * 如果端口部分没有设置，或者端口不是标准端口，**不应该** 包含在返回值内。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.2
     */
    public function getAuthority(): string
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;
        $userInfo  = $this->getUserInfo();

        /**
         * URI的授权语法是:
         *
         * [user-info@]host[:port]
         */
        if ('' !== $userInfo) {
            $authority = $userInfo . '@' . $authority;
        }

        if (null !== $this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    /**
     * 从 URI 中获取用户信息。
     *
     * 如果不存在用户信息，此方法 **必须** 返回一个空字符串。
     * 如果 URI 中存在用户，则返回该值；此外，如果密码也存在，它将附加到用户值，用冒号（「:」）分隔。
     * 用户信息后面跟着的 "@" 字符，不是用户信息里面的一部分，**不得** 在返回值里出现。
     */
    public function getUserInfo(): string
    {
        if ($this->pass) {
            return $this->user . ':' . $this->pass;
        }

        return $this->user;
    }

    /**
     * 从 URI 中取出 scheme。
     *
     * 如果不存在 Scheme，此方法 **必须** 返回空字符串。
     * 根据 RFC 3986 规范 3.1 章节，返回的数据 **必须** 是小写字母。
     * 最后部分的「:」字串不属于 Scheme，**不得** 作为返回数据的一部分。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-3.1
     */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /**
     * 从 URI 中获取 HOST 信息。
     *
     * 如果 URI 中没有此值，**必须** 返回空字符串。
     * 根据 RFC 3986 规范 3.2.2 章节，返回的数据 **必须** 是小写字母。
     *
     * @see http://tools.ietf.org/html/rfc3986#section-3.2.2
     */
    public function getHost(): string
    {
        return $this->host;
    }

    /**
     * 从 URI 中获取端口信息。
     *
     * 如果端口信息是与当前 Scheme 的标准端口不匹配的话，就使用整数值的格式返回，如果是一
     * 样的话，**应该** 返回 `null` 值。
     * 
     * 如果不存在端口和 Scheme 信息，**必须** 返回 `null` 值。
     * 
     * 如果不存在端口数据，但是存在 Scheme 的话，**可能** 返回 Scheme 对应的
     * 标准端口，但是 **应该** 返回 `null`。
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * 从 URI 中获取路径信息。
     *
     * 路径可以是空的，或者是绝对的（以斜线「/」开头），或者相对路径（不以斜线开头）。
     * 实现 **必须** 支持所有三种语法。
     *
     * 根据 RFC 7230 第 2.7.3 节，通常空路径「」和绝对路径「/」被认为是相同的。
     * 但是这个方法 **不得** 自动进行这种规范化，因为在具有修剪的基本路径的上下文中，
     * 例如前端控制器中，这种差异将变得显著。用户的任务就是可以将「」和「/」都处理好。
     *
     * 返回的值 **必须** 是百分号编码，但 **不得** 对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅 RFC 3986 第 2 节和第 3.3 节。
     *
     * 例如，如果值包含斜线（「/」）而不是路径段之间的分隔符，则该值必须以编码形式（例如「%2F」）
     * 传递给实例。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * 获取 URI 中的查询字符串。
     *
     * 如果不存在查询字符串，则此方法必须返回空字符串。
     *
     * 前导的「?」字符不是查询字符串的一部分，**不得** 添加在返回值中。
     *
     * 返回的值 **必须** 是百分号编码，但 **不得** 对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅 RFC 3986 第 2 节和第 3.4 节。
     *
     * 例如，如果查询字符串的键值对中的值包含不做为值之间分隔符的（「&」），则该值必须
     * 以编码形式传递（例如「%26」）到实例。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     */
    public function getQuery(): string
    {
        return $this->query;
    }

    /**
     * 获取 URI 中的片段（Fragment）信息。
     *
     * 如果没有片段信息，此方法 **必须** 返回空字符串。
     *
     * 前导的「#」字符不是片段的一部分，**不得** 添加在返回值中。
     *
     * 返回的值 **必须** 是百分号编码，但 **不得** 对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅 RFC 3986 第 2 节和第 3.5 节。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /**
     * 返回具有指定 Scheme 的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含指定 Scheme 的实例。
     *
     * 实现 **必须** 支持大小写不敏感的「http」和「https」的 Scheme，并且在
     * 需要的时候 **可能** 支持其他的 Scheme。
     *
     * 空的 Scheme 相当于删除 Scheme。
     *
     * @param string $scheme
     * @return self 具有指定 Scheme 的新实例。
     * @throws \InvalidArgumentException 使用无效的 Scheme 时抛出。
     * @throws \InvalidArgumentException 使用不支持的 Scheme 时抛出。
     */
    public function withScheme($scheme)
    {
        $this->checkStringParameter($scheme);
        $scheme = $this->filterScheme($scheme);
        return $this->processWith($scheme, 'scheme');
    }

    /**
     * 返回具有指定用户信息的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含指定用户信息的实例。
     *
     * 密码是可选的，但用户信息 **必须** 包括用户；用户信息的空字符串相当于删除用户信息。
     * 
     * @param string $user 用于认证的用户名。
     * @param null|string $password 密码。
     * @return self 具有指定用户信息的新实例。
     */
    public function withUserInfo($user, $password = null)
    {
        $this->checkStringParameter($user);

        if (null !== $password) {
            $this->checkStringParameter($user);
            $password = rawurlencode($password);
        }

        $user              = rawurlencode($user);
        $newInstance       = $this->cloneInstance($user, "user");
        $newInstance->pass = $password;

        return $newInstance;
    }

    /**
     * 返回具有指定 HOST 信息的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含指定 HOST 信息的实例。
     *
     * 空的 HOST 信息等同于删除 HOST 信息。
     *
     * @param string $host 用于新实例的 HOST 信息。
     * @return self 具有指定 HOST 信息的实例。
     * @throws \InvalidArgumentException 使用无效的 HOST 信息时抛出。
     */
    public function withHost($host)
    {
        return $this->processWith($host, "host");
    }

    /**
     * 返回具有指定端口的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含指定端口的实例。
     *
     * 实现 **必须** 为已建立的 TCP 和 UDP 端口范围之外的端口引发异常。
     *
     * 为端口提供的空值等同于删除端口信息。
     *
     * @param null|int $port 用于新实例的端口；`null` 值将删除端口信息。
     * @return self 具有指定端口的实例。
     * @throws \InvalidArgumentException 使用无效端口时抛出异常。
     */
    public function withPort($port)
    {
        if (null !== $port) {
            $port = $this->filterPort($port);

            if (null !== $port && ($port < 1 || $port > 65535)) {
                throw new InvalidArgumentException("Method expects valid port (1-65535)");
            }
        }

        return $this->cloneInstance($port, "port");
    }

    /**
     * 返回具有指定路径的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含指定路径的实例。
     *
     * 路径可以是空的、绝对的（以斜线开头）或者相对路径（不以斜线开头），实现必须支持这三种语法。
     *
     * 如果 HTTP 路径旨在与 HOST 相对而不是路径相对，，那么它必须以斜线开头。
     * 假设 HTTP 路径不以斜线开头，对应该程序或开发人员来说，相对于一些已知的路径。
     *
     * 用户可以提供编码和解码的路径字符，要确保实现了 `getPath()` 中描述的正确编码。
     *
     * @param string $path 用于新实例的路径。
     * @return self 具有指定路径的实例。
     * @throws \InvalidArgumentException 使用无效的路径时抛出。
     */
    public function withPath($path)
    {
        $this->checkStringParameter($path);

        if (false !== strpos($path, "?") || false !== strpos($path, "#")) {
            throw new InvalidArgumentException("Path cannot contain a query string or fragment");
        }

        $path = $this->filterPath($path);

        return $this->cloneInstance($path, "path");
    }

    /**
     * 返回具有指定查询字符串的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含查询字符串的实例。
     *
     * 用户可以提供编码和解码的查询字符串，要确保实现了 `getQuery()` 中描述的正确编码。
     *
     * 空查询字符串值等同于删除查询字符串。
     *
     * @param string $query 用于新实例的查询字符串。
     * @return self 具有指定查询字符串的实例。
     * @throws \InvalidArgumentException 使用无效的查询字符串时抛出。
     */
    public function withQuery($query)
    {
        $this->checkStringParameter($query);

        if (false !== strpos($query, "#")) {
            throw new InvalidArgumentException("Query cannot contain a query fragment");
        }

        $query = $this->filterQuery($query);

        return $this->cloneInstance($query, "query");
    }

    /**
     * 返回具有指定 URI 片段（Fragment）的实例。
     *
     * 此方法 **必须** 保留当前实例的状态，并返回包含片段的实例。
     *
     * 用户可以提供编码和解码的片段，要确保实现了 `getFragment()` 中描述的正确编码。
     *
     * 空片段值等同于删除片段。
     *
     * @param string $fragment 用于新实例的片段。
     * @return self 具有指定 URI 片段的实例。
     */
    public function withFragment($fragment)
    {
        $this->checkStringParameter($fragment);

        $fragment = $this->filterFragment($fragment);

        return $this->cloneInstance($fragment, "fragment");
    }

    /**
     * 如果传递的值为空，则返回以传递的参数作为前缀和后缀的值
     * 否则返回拼接三个字符串内容
     */
    private function checkValue(
        string $value,
        string $prefix = '',
        string $suffix = ''
    ): string {
        if ('' !== $value) {
            $value = $prefix . $value . $suffix;
        }
        return $value;
    }

    /**
     * 如果不存在片段，则此方法必须返回空字符串。
     *
     * 前导“#”字符不是片段的一部分，不能添加。
     * 返回的值必须进行百分号编码（Percent-encoding），但不能对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅RFC 3986第2节和第3.5节。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.5
     *
     * rawurlencode 与 urlencode 区别见：
     * @see https://www.jianshu.com/p/99c09270ad52
     */
    private function filterFragment(string $fragment): string
    {
        return rawurlencode($fragment);
    }

    /**
     *
     * 路径可以为空，也可以是绝对路径（以斜杠开始），也可以是相对路径（不以斜杠开始）
     * 。实现必须支持所有三种语法。
     *
     * 通常，按照 RFC 7230 第 2.7.3 节的定义，认为空路径 “” 和绝对路径 “/” 相等。
     * 但是这种方法不能自动进行这种标准化，因为在具有修剪的基本路径的上下文中，
     * 例如前控制器，这种差异变得显著。用户的任务是同时处理 “” 和 “/”。
     *
     * 返回的值必须进行百分号编码，但不能对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅 RFC 3986 第2节和第 3.3 节。
     *
     * 例如，如果该值应包含一个斜杠（“/”），
     * 而不是用作路径段之间的分隔符，则该值必须以编码形式（例如“%2F”）传递给实例。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.3
     *
     * @param string $path
     *
     * @return string The URI path.
     */
    private function filterPath(string $path): string
    {
        if ('' === $path || $path[0] === '/') {
            return $path;
        }

        $parts = explode('/', $path);
        foreach ($parts as $key => $element) {
            $parts[$key] = rawurlencode($element);
        }

        $path = implode('/', $parts);

        return '/' . ltrim($path, '/');
    }

    /**
     * 检查端口号，如果端口为 80 或者 443 则次方法应该返回 null
     *
     * @param int|null $port
     * @return int|null
     */
    private function filterPort($port)
    {
        $ports = [
            80 => true,
            443 => true,
        ];

        if ($port) {
            $port = (int) $port;
            if (isset($ports[$port])) {
                $port = null;
            }
        }

        return $port;
    }

    /**
     * 如果不存在查询字符串，则此方法必须返回空字符串。
     * 起始的“？”字符不是查询的一部分，不能添加。
     *
     * 返回的值必须进行百分号编码，不能对任何字符进行双重编码。
     * 要确定要编码的字符，请参阅 RFC 3986 第 2 节和第 3.4 节。
     *
     * 例如，如果查询字符串的键/值对中的值应包含一个不打算用作值之间分隔符的符号（&），
     * 则该值必须以编码形式（例如，“%26”）传递给实例。
     *
     * @see https://tools.ietf.org/html/rfc3986#section-2
     * @see https://tools.ietf.org/html/rfc3986#section-3.4
     */
    private function filterQuery(string $query): string
    {
        if ('' === $query) {
            return '';
        }

        $query = ltrim($query, '?');
        $parts = explode('&', $query);

        foreach ($parts as $key => $part) {
            $split = $this->splitQueryValue($part);
            if (null === $split[1]) {
                $parts[$key] = rawurlencode($split[0]);
            } else {
                $parts[$key] = rawurlencode($split[0]) . '=' . rawurlencode($split[1]);
            }
        }

        return implode('&', $parts);
    }

    /**
     * query 字符串分割为数组
     */
    private function splitQueryValue(string $elemen): array
    {
        $data = explode('=', $elemen, 2);
        $data[1] = $data[1] ?? null;
        return $data;
    }

    /**
     * 过滤传递过来的 scheme
     */
    private function filterScheme(string $scheme): string
    {
        $filtered = preg_replace("#:(//)?$#", "", strtolower($scheme));
        if ('' === $filtered) {
            return '';
        }

        $schemes  = [
            'http'  => true,
            'https' => false,
        ];

        if (! isset($schemes[$filtered])) {
            throw new InvalidArgumentException(
                "不被支持的 scheme [{$filtered}]. Scheme 必须为 [" . implode(', ', array_keys($schemes)) . '] 之一'
            );
        }

        return $scheme;
    }
}
