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

use JsonException;

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

        if ($uploadedFiles = static::getUploadedFiles($_FILES)) {
            $request = $request->withUploadedFiles($uploadedFiles);
        }

        return $request;
    }

    /**
     * @throws JsonException
     * @return array
     */
    protected static function getParsedBody(): array
    {
        $result = $_POST;
        $contents = static::getBody()->getContents();

        if (static::isForm() && $contents) {
            parse_str($contents, $result);
        } elseif (!$result && $contents) {
            $result = json_decode(
                $contents,
                true,
                512,
                JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR
            );
        }

        return $result;
    }

    /**
     * @param  array $files
     * @return array
     */
    protected static function getUploadedFiles(array $files): array
    {
        $result = [];

        if ($files) {
            if (\is_array(current($files)['name'])) {
                $files = static::convertFileData($files);
            }
            foreach ($files as $file) {
                $stream = Stream::create($file['tmp_name']);
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

    /**
     * @param  array $files
     * @return array
     */
    protected static function convertFileData(array $files): array
    {
        $result = [];

        foreach (current($files) as $key => $items) {
            foreach ($items as $id => $item) {
                $result[$id][$key] = $item;
            }
        }

        return $result;
    }
}
