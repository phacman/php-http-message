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

enum StrTrEnum: string
{
    case FROM = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    case TO = 'abcdefghijklmnopqrstuvwxyz';
}
