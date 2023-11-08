<?php

declare(strict_types=1);

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhacMan\HttpMessage;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Michael Dowling and contributors to guzzlehttp/psr7
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class ServerRequest implements ServerRequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /** @var array */
    private array $attributes = [];

    /** @var array */
    private array $cookieParams = [];

    /** @var array|object|null */
    private array|null|object $parsedBody = null;

    /** @var array */
    private array $queryParams = [];

    /** @var array */
    private array $serverParams;

    /** @var UploadedFileInterface[] */
    private array $uploadedFiles = [];

    /**
     * @param string                      $method       HTTP method
     * @param string|UriInterface         $uri          URI
     * @param array                       $headers      Request headers
     * @param StreamInterface|string|null $body         Request body
     * @param string                      $version      Protocol version
     * @param array                       $serverParams Typically the $_SERVER superglobal
     */
    public function __construct(string $method, UriInterface|string $uri, array $headers = [], StreamInterface|string $body = null, string $version = '1.1', array $serverParams = [])
    {
        $this->serverParams = $serverParams;

        if (!($uri instanceof UriInterface)) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;
        parse_str($uri->getQuery(), $this->queryParams);

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        // If we got no body, defer initialization of the stream until ServerRequest::getBody()
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
    }

    /** {@inheritdoc} */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /** {@inheritdoc} */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /** {@inheritdoc} */
    public function withUploadedFiles(array $uploadedFiles): ServerRequestInterface
    {
        $new = clone $this;
        $new->uploadedFiles = $uploadedFiles;

        return $new;
    }

    /** {@inheritdoc} */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /** {@inheritdoc} */
    public function withCookieParams(array $cookies): ServerRequestInterface
    {
        $new = clone $this;
        $new->cookieParams = $cookies;

        return $new;
    }

    /** {@inheritdoc} */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /** {@inheritdoc} */
    public function withQueryParams(array $query): ServerRequestInterface
    {
        $new = clone $this;
        $new->queryParams = $query;

        return $new;
    }

    /** {@inheritdoc} */
    public function getParsedBody(): object|array|null
    {
        return $this->parsedBody;
    }

    /** {@inheritdoc} */
    public function withParsedBody($data): ServerRequestInterface
    {
        if (!\is_array($data) && !\is_object($data) && null !== $data) {
            throw new \InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
        }

        $new = clone $this;
        $new->parsedBody = $data;

        return $new;
    }

    /** {@inheritdoc} */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /** {@inheritdoc} */
    public function getAttribute(string $name, $default = null)
    {
        if (false === \array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /** {@inheritdoc} */
    public function withAttribute(string $name, $value): ServerRequestInterface
    {
        $new = clone $this;
        $new->attributes[$name] = $value;

        return $new;
    }

    /** {@inheritdoc} */
    public function withoutAttribute(string $name): ServerRequestInterface
    {
        if (false === \array_key_exists($name, $this->attributes)) {
            return $this;
        }

        $new = clone $this;
        unset($new->attributes[$name]);

        return $new;
    }
}
