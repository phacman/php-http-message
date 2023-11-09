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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Martijn van der Ven <martijn@vanderven.se>
 * @author Pavel Vasin <phacman@yandex.ru>
 *
 * @final This class should never be extended. See https://github.com/Nyholm/psr7/blob/master/doc/final.md
 */
class HttpFactory implements HttpFactoryInterface
{
    /** {@inheritdoc} */
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }

    /** {@inheritdoc} */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (2 > \func_num_args()) {
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    /** {@inheritdoc} */
    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    /** {@inheritdoc} */
    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    /** {@inheritdoc} */
    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        if ('' === $filename) {
            throw new \RuntimeException('Filename cannot be empty');
        }

        if (false === $resource = @fopen($filename, $mode)) {
            if ('' === $mode || false === \in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
                throw new \InvalidArgumentException(sprintf('The mode "%s" is invalid.', $mode));
            }

            $message = sprintf(
                'The file "%s" cannot be opened: %s',
                $filename,
                error_get_last()['message'] ?? ''
            );

            throw new \RuntimeException($message);
        }

        return Stream::create($resource);
    }

    /** {@inheritdoc} */
    public function createStreamFromResource(mixed $resource): StreamInterface
    {
        return Stream::create($resource);
    }

    /** {@inheritdoc} */
    public function createUploadedFile(StreamInterface $stream, int $size = null, int $error = UPLOAD_ERR_OK, string $clientFilename = null, string $clientMediaType = null): UploadedFileInterface
    {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    /** {@inheritdoc} */
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
