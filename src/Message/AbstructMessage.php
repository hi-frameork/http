<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use Hi\Helpers\Arr;
use Hi\Helpers\Exceptions\InvalidArgumentException;
use Hi\Helpers\StatusCode;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use function array_key_exists;
use function array_merge;
use function preg_match;

abstract class AbstractMessage extends AbstructCommon implements MessageInterface
{
    /**
     * @var StreamInterface
     */
    protected $body;

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var string
     */
    protected $protocolVersion = '1.1';

    /**
     * @var UriInterface
     */
    protected $uri;

    public function getHeader($name): array
    {
        return Arr::get($this->headers, $name, []);
    }

    public function getHeaderLine($name): string
    {
        $header = $this->getHeader($name);
        return implode(',', $header);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        $name = (string) $name;
        return array_key_exists($name, $this->headers);
    }

    public function withAddedHeader($name, $value)
    {
        $this->checkHeaderName($name);

        $existing = $this->getHeader($name);
        $value    = $this->getHeaderValue($value);
        $value    = array_merge($existing, $value);

        $headers        = $this->headers;
        $headers[$name] = $value;

        return $this->cloneInstance($headers, 'headers');
    }

    public function withBody(StreamInterface $body)
    {
        $newBody = $this->processBody($body, 'w+b');
        return $this->cloneInstance($newBody, 'body');
    }

    public function withHeader($name, $value)
    {
        $this->checkHeaderName($name);

        $value          = $this->getHeaderValue($value);
        $headers        = $this->headers;
        $headers[$name] = $value;

        return $this->cloneInstance($this->headers, 'headers');
    }

    public function withProtocolVersion($version)
    {
        $this->processProtocol($version);
        return $this->cloneInstance($version, 'protocolVersion');
    }

    public function withoutHeader($name)
    {
        $headers = $this->headers;
        unset($headers[$name]);
        return $this->cloneInstance($headers, 'headers');
    }

    final protected function checkHeaderHost(array $collextion): array
    {
        if (isset($collextion['host']) && ! $this->uri && $this->uri->getHost()) {
            $host               = $this->getUriHost($this->uri);
            $collextion['host'] = (array) $host;
        }

        return $collextion;
    }

    final protected function getUriHost(UriInterface $uri): string
    {
        $host = $uri->getHost();

        if (null !== $uri->getPort()) {
            $host .= ':' . $uri->getPort();
        }

        return $host;
    }

    final protected function checkHeaderName($name): void
    {
        if (! is_string($name) || !preg_match("/^[a-zA-Z0-9'`#$%&*+.^_|~!-]+$/", $name)) {
            throw new InvalidArgumentException("Header 名无效{$name}", StatusCode::E_500000);
        }
    }

    final protected function checkHeaderValue($value)
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new InvalidArgumentException(
                'Header 值无效',
                StatusCode::E_500000,
                [
                    'value' => $value
                ]
            );
        }

        $value = (string) $value;

        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value) ||
            preg_match("/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/", $value)) {
            throw new InvalidArgumentException(
                'Header 值无效',
                StatusCode::E_500000,
                [
                    'value' => $value
                ]
            );
        }
    }

    final protected function getHeaderValue($values): array
    {
        $valueArray = $values;
        if (! is_array($values)) {
            $valueArray = [$values];
        }

        if (empty($valueArray)) {
            throw new InvalidArgumentException(
                'Heaer 值无效：必须为 string 或 string 数组',
                StatusCode::E_500000,
                [
                    'values' => $values,
                    'valueArray' => $valueArray,
                ]
            );
        }

        $valueData = [];
        foreach ($valueArray as $value) {
            $this->checkHeaderValue($value);
            $valueData[] = $value;
        }

        return $valueData;
    }

    final protected function processBody($body = 'php://memory', string $mode = 'r+b'): StreamInterface
    {
        if (is_object($body) && $body instanceof StreamInterface) {
            return $body;
        }

        if (! is_object($body) && ! is_string($body)) {
            throw new InvalidArgumentException(
                'Steam 参数无效',
                StatusCode::E_500000,
                [
                    'body' => $body,
                ]
            );
        }

        return new Stream($body, $mode);
    }

    final protected function processHeaders($headers):array
    {
    }

    final protected function processProtocol($protocal = ''): string
    {
        $protocols = [
            "1.0" => 1,
            "1.1" => 1,
            "2.0" => 1,
            "3.0" => 1,
        ];

        if (! $protocal || ! is_string($protocal)) {
            throw new InvalidArgumentException(
                'Protocal 值无效',
                StatusCode::E_500000,
                [
                    'protocal' => $protocal,
                ]
            );
        }

        if (! isset($protocols[$protocal])) {
            throw new InvalidArgumentException(
                "Protocal 不被支持：{$protocal}",
                StatusCode::E_500000,
                [
                    'protocal' => $protocal,
                ]
            );
        }

        return $protocal;
    }
}
