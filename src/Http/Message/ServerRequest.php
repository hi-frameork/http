<?php

declare(strict_types=1);

namespace Hi\Http\Message;

use function array_key_exists;
use function get_class;
use function gettype;

use InvalidArgumentException;

use function is_array;

use function is_object;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

use function sprintf;

class ServerRequest extends Request implements ServerRequestInterface
{
    /**
     * @var array
     */
    protected array $attributes = [];

    /**
     * @var array
     */
    protected array $cookieParams;

    /**
     * @var array|object|null
     */
    protected $parsedBody;

    /**
     * @var array
     */
    protected array $queryParams;

    /**
     * @var array
     */
    protected array $serverParams;

    /**
     * @var array
     */
    protected array $uploadedFiles;

    /**
     * @param array                                $serverParams
     * @param array                                $uploadedFiles
     * @param array                                $cookieParams
     * @param array                                $queryParams
     * @param array|object|null                    $parsedBody
     * @param string                               $method
     * @param UriInterface|string                  $uri
     * @param array                                $headers
     * @param StreamInterface|string|resource|null $body
     * @param string                               $protocol
     */
    public function __construct(
        array $serverParams = [],
        array $uploadedFiles = [],
        array $cookieParams = [],
        array $queryParams = [],
        $parsedBody = null,
        string $method = 'GET',
        $uri = '',
        array $headers = [],
        $body = null,
        string $protocol = '1.1'
    ) {
        $this->validateUploadedFiles($uploadedFiles);
        $this->uploadedFiles = $uploadedFiles;
        $this->serverParams  = $serverParams;
        $this->cookieParams  = $cookieParams;
        $this->queryParams   = $queryParams;
        $this->parsedBody    = $parsedBody;
        $this->init($method, $uri, $headers, $body, $protocol);
    }

    /**
     * @inheritDoc
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new               = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new              = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * @inheritDoc
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    public function withParsedBody($data): ServerRequestInterface
    {
        if (!is_array($data) && !is_object($data) && $data !== null) {
            throw new InvalidArgumentException(sprintf(
                '"%s" is not valid Parsed Body. It must be a null, an array, or an object.',
                gettype($data)
            ));
        }

        $new             = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }

        return $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value): ServerRequestInterface
    {
        if (array_key_exists($name, $this->attributes) && $this->attributes[$name] === $value) {
            return $this;
        }

        $new                    = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /**
     * @inheritDoc
     */
    public function withoutAttribute($name): ServerRequestInterface
    {
        if (!array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }

    /**
     * @param  array                    $uploadedFiles
     * @throws InvalidArgumentException
     * @psalm-suppress MixedAssignment
     */
    protected function validateUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);

                continue;
            }

            if (!$file instanceof UploadedFileInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid item in uploaded files structure.'
                    . '"%s" is not an instance of "\Psr\Http\Message\UploadedFileInterface".',
                    (is_object($file) ? get_class($file) : gettype($file))
                ));
            }
        }
    }

    public function getParameter(string $source, string $name, $defaultValue = null)
    {
        switch ($source) {
            case 'query':
                return $this->queryParams[$name] ?? $defaultValue;

                break;
            case 'post':
            case 'put':
                return $this->parsedBody[$name] ?? $defaultValue;

                break;
            case 'header':
                return $this->getHeaderLine($name);

                break;
            case 'server':
                return $this->serverParams[$name] ?? $defaultValue;

                break;
            case 'attribute':
                return $this->getAttribute($name, $defaultValue);

                break;
            case 'cookie':
                return $this->cookieParams[$name] ?? $defaultValue;

                break;
        }
    }
}
