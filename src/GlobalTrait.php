<?php

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhacMan\HttpMessage;

use Psr\Http\Message\StreamInterface;

/**
 * Common methods for: RequestGlobal, ServerRequestGlobal.
 *
 * @author Pavel Vasin <phacman@yandex.ru>
 */
trait GlobalTrait
{
    private static bool $form = false;

    /**
     * @return bool
     */
    public static function isForm(): bool
    {
        return self::$form;
    }

    /**
     * @return string
     */
    protected static function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * @return string
     */
    protected static function getUri(): string
    {
        // http://username:password@localhost:8972/hello/world?k1=v1&k2=v2#some-hash

        $scheme = static::isSecure() ? 'https' : 'http';
        $userinfo = static::getUserinfo();
        $host = sprintf('%s:%s', $_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);
        $host = rtrim($host, ':');
        $authority = ($userinfo ? $userinfo.'@' : $userinfo).$host;
        $path = $_SERVER['PATH_INFO'];
        $query = static::getQueryString();

        return sprintf('%s://%s%s%s', $scheme, $authority, $path, $query);
    }

    /**
     * @return StreamInterface
     */
    protected static function getBody(): StreamInterface
    {
        $resource = @fopen('php://input', 'r');

        return Stream::create($resource);
    }

    /**
     * @return string
     */
    protected static function getProtocolVersion(): string
    {
        $parts = explode('/', $_SERVER['SERVER_PROTOCOL'] ?? '');
        $version = end($parts);

        return \is_string($version) && '' !== $version ? $version : '1.1';
    }

    /**
     * @param  bool  $ucf first letter is capitalized
     * @return array
     */
    protected static function getHeaders(bool $ucf = false): array
    {
        $headers = [];

        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = strtolower(str_replace('_', '-', substr($name, 5)));

                if ($ucf && !str_contains($key, '-ua-')) {
                    $items = explode('-', $key);
                    $key = implode('-', array_map('ucfirst', $items));
                }

                if ('content-type' === strtolower($key)
                    && 'application/x-www-form-urlencoded' === $value
                ) {
                    self::$form = true;
                }

                $headers[$key] = $value;
            }
        }

        return $headers;
    }

    protected static function getQueryString(): string
    {
        $qs = $_SERVER['QUERY_STRING'] ?? '';

        if ($qs) {
            $qs = '?'.rawurldecode($qs);
        }

        return $qs;
    }

    /**
     * @return string
     */
    protected static function getUserinfo(): string
    {
        $username = $_SERVER['PHP_AUTH_USER'] ?? '';
        $password = $_SERVER['PHP_AUTH_PW'] ?? '';

        return $username && $password
            ? sprintf('%s:%s', $username, $password)
            : '';
    }

    /**
     * @return bool
     */
    protected static function isSecure(): bool
    {
        return (
            isset($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS']
        ) || 443 == $_SERVER['SERVER_PORT'];
    }
}
