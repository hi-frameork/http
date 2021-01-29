<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Arr;
use Hi\Helpers\Exceptions\InvalidArgumentException;
use Hi\Helpers\StatusCode;
use Psr\Http\Message\UriInterface;
use function strtolower;
use function preg_replace;
use function explode;
use function implode;
use function rawurlencode;
use function ltrim;
use function parse_url;
use function strpos;


/**
 * PSR-7 Uri
 */
final class Uri extends AbstructCommon implements UriInterface
{
    /**
     * URI fragment
     */
    protected string $fragment = '';

    /**
     * URI host
     */
    protected string $host = '';

    /**
     * URI 携带用户名
     */
    protected string $user = '';

    /**
     * URI 携带密码
     */
    protected string $pass = '';

    /**
     * URI path
     */
    protected string $path = '';

    /**
     * URL 端口
     */
    protected $port;

    /**
     * URI 查询参数
     */
    protected string $query = '';

    /**
     * URI scheme
     */
    protected string $scheme = 'https';

    public function __construct(string $uri = '')
    {
        if ($uri) {
            $urlParts = parse_url($uri);
            if (false === $urlParts) {
                $urlParts = [];
            }

            $this->scheme   = $this->filterScheme(Arr::get($urlParts, 'scheme', ''));
            $this->user     = rawurlencode(Arr::get($urlParts, 'user', ''));
            $this->pass     = rawurlencode(Arr::get($urlParts, 'pass', ''));
            $this->host     = strtolower(Arr::get($urlParts, 'host', ''));
            $this->port     = $this->filterPort(Arr::get($urlParts, 'port', null));
            $this->path     = $this->filterPath(Arr::get($urlParts, 'path', ''));
            $this->query    = $this->filterQuery(Arr::get($urlParts, 'query', ''));
            $this->fragment = $this->filterFragment(Arr::get($urlParts, 'fragment', ''));
        }
    }

    public function __toString(): string
    {
        $authority = $this->getAuthority();
        $path      = $this->path;

        if ('' !== $path && '/' !== $path[0] && '' !== $authority) {
            $path = '/' . $path;
        }

        $uri = $this->checkValue($this->scheme, '', ':')
             . $this->checkValue($authority, '//')
             . $path
             . $this->checkValue($this->query, '?')
             . $this->checkValue($this->fragment, '#');

        return $uri;
    }

    public function getAuthority()
    {
        if (! $this->host) {
            return '';
        }

        $authority = $this->host;
        $userInfo  = $this->getUserInfo();

        if ($userInfo) {
            $authority = $userInfo . '@' . $authority;
        }

        if ($this->port) {
            $authority .= ':' . $this->port;
        }

        return $authority;
    }

    public function getUserInfo()
    {
        if ($this->pass) {
            return $this->user . ':' . $this->pass;
        }

        return $this->user;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getFragment()
    {
        return $this->fragment;
    }

    public function withFragment($fragment)
    {
        $this->checkStringParameter($fragment);
        $fragment = $this->filterFragment($fragment);
        return $this->cloneInstance($fragment, 'fragment');
    }

    public function withPath($path)
    {
        $this->checkStringParameter($path);

        if (false !== strpos($path, '?') || false !== strpos($path, '#')) {
            throw new InvalidArgumentException('Path 不能包含 query string 或 fragment');
        }

        $path = $this->filterPath($path);

        return $this->cloneInstance($path, 'path');
    }

    public function withPort($port)
    {
        if ($port) {
            $port = $this->filterPort($port);

            if (null !== $port && ($port < 1 || $port > 65535)) {
                throw new InvalidArgumentException('Port 值范围必须在(1-65535)');
            }
        }

        return $this->cloneInstance($port, 'port');
    }

    public function withQuery($query)
    {
        $this->checkStringParameter($query);

        if (false !== strpos($query, '#')) {
            throw new InvalidArgumentException('Query string 不能包含 fragment');
        }

        $query = $this->filterQuery($query);

        return $this->cloneInstance($query, 'query');
    }

    public function withScheme($scheme)
    {
        $this->checkStringParameter($scheme);

        $scheme = $this->filterScheme($scheme);

        return $this->cloneInstance($scheme, 'scheme');
    }

    public function withUserInfo($user, $password = null)
    {
        $this->checkStringParameter($user);

        if (null !== $password) {
            $this->checkStringParameter($password);
            $password = rawurlencode($password);
        }

        $user = rawurlencode($user);

        $newInstance = $this->cloneInstance($user, 'user');
        $newInstance->pass = $password;

        return $newInstance;
    }

    public function withHost($host)
    {
        return $this->processWith($host, 'host');
    }

    private function checkValue(string $value, string $prefix = '', string $suffix = '')
    {
        if ($value) {
            $value = $prefix . $value . $suffix;
        }

        return $value;
    }

    private function filterFragment(string $fragment): string
    {
        return rawurlencode($fragment);
    }

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

    private function filterPort($port)
    {
        $ports = [
            80 => true,
            443 => true,
        ];

        if ($port) {
            $port = (int) $port;
            if (empty($ports[$port])) {
                $port = null;
            }
        }

        return $port;
    }

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

    private function splitQueryValue(string $elemen): array
    {
        $data = explode('=', $elemen, 2);
        $data[1] = $data[1] ?? null;
        return $data;
    }

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
                "Unsupported scheme [{$filtered}]. Scheme must be one of [" . implode(', ', array_keys($schemes)) . ']',
                StatusCode::E_500000,
                [
                    'scheme' => $scheme,
                ]
            );
        }

        return $scheme;
    }
}
