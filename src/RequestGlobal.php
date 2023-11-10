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
 * Creates a new request with values from globals.
 *
 * @author Pavel Vasin <phacman@yandex.ru>
 */
class RequestGlobal
{
    use GlobalTrait;

    /**
     * @param  bool    $ucf first letter is capitalized
     * @return Request
     */
    public static function create(bool $ucf = false): Request
    {
        return new Request(
            static::getMethod(),
            static::getUri(),
            static::getHeaders($ucf),
            static::getBody(),
            static::getProtocolVersion()
        );
    }
}
