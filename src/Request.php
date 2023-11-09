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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class Request implements RequestInterface
{
    use MessageTrait;
    use RequestTrait;

    /**
     * @param string                      $method  HTTP method
     * @param string|UriInterface         $uri     URI
     * @param array                       $headers Request headers
     * @param StreamInterface|string|null $body    Request body
     * @param string                      $version Protocol version
     */
    public function __construct(string $method, UriInterface|string $uri, array $headers = [], StreamInterface|string $body = null, string $version = '1.1')
    {
        if (!$uri instanceof UriInterface) {
            $uri = new Uri($uri);
        }

        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        // if we don't have anything, defer initialization of the stream until Request::getBody()
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
    }
}
