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

/**
 * Creates a new server request with values from globals.
 *
 * @author Pavel Vasin <phacman@yandex.ru>
 */
class ServerRequestGlobal
{
    use GlobalTrait;

    /**
     * @param  bool          $ucf first letter is capitalized
     * @return ServerRequest
     */
    public static function create(bool $ucf = false): ServerRequest
    {
        $server = new ServerRequest(
            static::getMethod(),
            static::getUri(),
            static::getHeaders($ucf),
            static::getBody(),
            static::getProtocolVersion(),
            $_SERVER
        );

        $parsedBody = static::getParsedBody();

        $request = $server
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($parsedBody);

        if ($uploadedFiles = static::getUploadedFiles()) {
            $request = $request->withUploadedFiles($uploadedFiles);
        }

        return $request;
    }

    /**
     * @return array
     */
    protected static function getParsedBody(): array
    {
        $result = $_POST;

        if (static::isForm()) {
            parse_str(static::getBody()->getContents(), $result);
        }

        return $result;
    }

    /**
     * @return array
     */
    protected static function getUploadedFiles(): array
    {
        $result = [];

        if ($_FILES) {
            foreach ($_FILES as $file) {
                $stream = fopen($file['tmp_name'], 'r');
                $result[] = new UploadedFile(
                    $stream,
                    $file['size'],
                    $file['error'],
                    $file['name'],
                    $file['type']
                );
            }
        }

        return $result;
    }
}
