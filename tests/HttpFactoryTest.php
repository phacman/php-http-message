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

namespace PhacMan\HttpMessage\Tests;

use PhacMan\HttpMessage\HttpFactory;
use PHPUnit\Framework\TestCase;

class HttpFactoryTest extends TestCase
{
    public function testCreateResponse()
    {
        $factory = new HttpFactory();
        $r = $factory->createResponse(200);
        $this->assertEquals('OK', $r->getReasonPhrase());

        $r = $factory->createResponse(200, '');
        $this->assertEquals('', $r->getReasonPhrase());

        $r = $factory->createResponse(200, 'Foo');
        $this->assertEquals('Foo', $r->getReasonPhrase());

        /*
         * Test for non-standard response codes
         */
        $r = $factory->createResponse(567);
        $this->assertEquals('', $r->getReasonPhrase());

        $r = $factory->createResponse(567, '');
        $this->assertEquals(567, $r->getStatusCode());
        $this->assertEquals('', $r->getReasonPhrase());

        $r = $factory->createResponse(567, 'Foo');
        $this->assertEquals(567, $r->getStatusCode());
        $this->assertEquals('Foo', $r->getReasonPhrase());
    }
}
