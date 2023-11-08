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

use Psr\Http\Message\UriInterface;

/**
 * PSR-7 URI implementation.
 *
 * @author Michael Dowling
 * @author Tobias Schultze
 * @author Matthew Weier O'Phinney
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Uri implements UriInterface
{
    private const SCHEMES = ['http' => 80, 'https' => 443];

    private const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';

    private const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

    private const CHAR_GEN_DELIMS = ':\/\?#\[\]@';

    /** @var string Uri scheme. */
    private string $scheme = '';

    /** @var string Uri user info. */
    private mixed $userInfo = '';

    /** @var string Uri host. */
    private string $host = '';

    /** @var int|null Uri port. */
    private ?int $port = null;

    /** @var string Uri path. */
    private string $path = '';

    /** @var string Uri query string. */
    private string $query = '';

    /** @var string Uri fragment. */
    private string $fragment = '';

    public function __construct(string $uri = '')
    {
        if ('' !== $uri) {
            if (false === $parts = parse_url($uri)) {
                throw new \InvalidArgumentException(sprintf('Unable to parse URI: "%s"', $uri));
            }

            // Apply parse_url parts to a URI.
            $this->scheme = isset($parts['scheme']) ? strtr($parts['scheme'], StrTrEnum::FROM->value, StrTrEnum::TO->value) : '';
            $this->userInfo = $parts['user'] ?? '';
            $this->host = isset($parts['host']) ? strtr($parts['host'], StrTrEnum::FROM->value, StrTrEnum::TO->value) : '';
            $this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
            $this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
            $this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
            $this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
            if (isset($parts['pass'])) {
                $this->userInfo .= ':'.$parts['pass'];
            }
        }
    }

    /** {@inheritdoc} */
    public function __toString(): string
    {
        return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
    }

    /** {@inheritdoc} */
    public function getScheme(): string
    {
        return $this->scheme;
    }

    /** {@inheritdoc} */
    public function getAuthority(): string
    {
        if ('' === $this->host) {
            return '';
        }

        $authority = $this->host;
        if ('' !== $this->userInfo) {
            $authority = $this->userInfo.'@'.$authority;
        }

        if (null !== $this->port) {
            $authority .= ':'.$this->port;
        }

        return $authority;
    }

    /** {@inheritdoc} */
    public function getUserInfo(): string
    {
        return $this->userInfo;
    }

    /** {@inheritdoc} */
    public function getHost(): string
    {
        return $this->host;
    }

    /** {@inheritdoc} */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /** {@inheritdoc} */
    public function getPath(): string
    {
        $path = $this->path;

        if ('' !== $path && '/' !== $path[0]) {
            if ('' !== $this->host) {
                // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                $path = '/'.$path;
            }
        } elseif (isset($path[1]) && '/' === $path[1]) {
            // If the path is starting with more than one "/", the
            // starting slashes MUST be reduced to one.
            $path = '/'.ltrim($path, '/');
        }

        return $path;
    }

    /** {@inheritdoc} */
    public function getQuery(): string
    {
        return $this->query;
    }

    /** {@inheritdoc} */
    public function getFragment(): string
    {
        return $this->fragment;
    }

    /** {@inheritdoc} */
    public function withScheme(string $scheme): UriInterface
    {
        if ($this->scheme === $scheme = strtr($scheme, StrTrEnum::FROM->value, StrTrEnum::TO->value)) {
            return $this;
        }

        $new = clone $this;
        $new->scheme = $scheme;
        $new->port = $new->filterPort($new->port);

        return $new;
    }

    /** {@inheritdoc} */
    public function withUserInfo(string $user, string $password = null): UriInterface
    {
        $info = preg_replace_callback('/['.self::CHAR_GEN_DELIMS.self::CHAR_SUB_DELIMS.']++/', [__CLASS__, 'rawUrlEncodeMatchZero'], $user);
        if (null !== $password && '' !== $password) {
            if (!\is_string($password)) {
                throw new \InvalidArgumentException('Password must be a string');
            }

            $info .= ':'.preg_replace_callback('/['.self::CHAR_GEN_DELIMS.self::CHAR_SUB_DELIMS.']++/', [__CLASS__, 'rawUrlEncodeMatchZero'], $password);
        }

        if ($this->userInfo === $info) {
            return $this;
        }

        $new = clone $this;
        $new->userInfo = $info;

        return $new;
    }

    /** {@inheritdoc} */
    public function withHost(string $host): UriInterface
    {
        if ($this->host === $host = strtr($host, StrTrEnum::FROM->value, StrTrEnum::TO->value)) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;

        return $new;
    }

    /** {@inheritdoc} */
    public function withPort(?int $port): UriInterface
    {
        if ($this->port === $port = $this->filterPort($port)) {
            return $this;
        }

        $new = clone $this;
        $new->port = $port;

        return $new;
    }

    /** {@inheritdoc} */
    public function withPath(string $path): UriInterface
    {
        if ($this->path === $path = $this->filterPath($path)) {
            return $this;
        }

        $new = clone $this;
        $new->path = $path;

        return $new;
    }

    /** {@inheritdoc} */
    public function withQuery(string $query): UriInterface
    {
        if ($this->query === $query = $this->filterQueryAndFragment($query)) {
            return $this;
        }

        $new = clone $this;
        $new->query = $query;

        return $new;
    }

    /** {@inheritdoc} */
    public function withFragment(string $fragment): UriInterface
    {
        if ($this->fragment === $fragment = $this->filterQueryAndFragment($fragment)) {
            return $this;
        }

        $new = clone $this;
        $new->fragment = $fragment;

        return $new;
    }

    /**
     * Create a URI string from its various parts.
     * @param  string $scheme
     * @param  string $authority
     * @param  string $path
     * @param  string $query
     * @param  string $fragment
     * @return string
     */
    protected static function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment): string
    {
        $uri = '';
        if ('' !== $scheme) {
            $uri .= $scheme.':';
        }

        if ('' !== $authority) {
            $uri .= '//'.$authority;
        }

        if ('' !== $path) {
            if ('/' !== $path[0]) {
                if ('' !== $authority) {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = '/'.$path;
                }
            } elseif (isset($path[1]) && '/' === $path[1]) {
                if ('' === $authority) {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = '/'.ltrim($path, '/');
                }
            }

            $uri .= $path;
        }

        if ('' !== $query) {
            $uri .= '?'.$query;
        }

        if ('' !== $fragment) {
            $uri .= '#'.$fragment;
        }

        return $uri;
    }

    /**
     * Is a given port non-standard for the current scheme?
     * @param string $scheme
     * @param int    $port
     */
    protected static function isNonStandardPort(string $scheme, int $port): bool
    {
        return !isset(self::SCHEMES[$scheme]) || $port !== self::SCHEMES[$scheme];
    }

    protected function filterPort(int|null $port): ?int
    {
        if (null === $port) {
            return null;
        }

        if (0 > $port || 0xFFFF < $port) {
            throw new \InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
        }

        return self::isNonStandardPort($this->scheme, $port) ? $port : null;
    }

    protected function filterPath(string $path): string
    {
        return preg_replace_callback('/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawUrlEncodeMatchZero'], $path);
    }

    protected function filterQueryAndFragment(string $str): string
    {
        return preg_replace_callback('/(?:[^'.self::CHAR_UNRESERVED.self::CHAR_SUB_DELIMS.'%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawUrlEncodeMatchZero'], $str);
    }

    private static function rawUrlEncodeMatchZero(array $match): string
    {
        return rawurlencode($match[0]);
    }
}
