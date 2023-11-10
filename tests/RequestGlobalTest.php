<?php

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhacMan\HttpMessage\Tests;

use PhacMan\HttpMessage\RequestGlobal;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhacMan\HttpMessage\RequestGlobal
 */
class RequestGlobalTest extends TestCase
{
    /**
     * @return void
     */
    public function testFullUri(): void
    {
        $uri = 'http://username:password@site.ru:8972/hello/world?day=good#some-hash';
        $_SERVER = $this->parseUrlToServer($uri);
        $request = RequestGlobal::create();
        $uri = $request->getUri();
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('username:password@site.ru:8972', $uri->getAuthority());
        $this->assertEquals('username:password', $uri->getUserInfo());
        $this->assertEquals('site.ru', $uri->getHost());
        $this->assertEquals(8972, $uri->getPort());
        $this->assertEquals('/hello/world', $uri->getPath());
        $this->assertEquals('day=good', $uri->getQuery());
        $this->assertEquals('some-hash', $uri->getFragment());
    }

    /**
     * @return void
     */
    public function testHttpsUri(): void
    {
        $uri = 'https://site.ru:8972/hello/world?day=good#some-hash';
        $_SERVER = $this->parseUrlToServer($uri);
        $request = RequestGlobal::create();
        $uri = $request->getUri();
        $this->assertEquals('https', $uri->getScheme());
        $this->assertEquals('site.ru:8972', $uri->getAuthority());
        $this->assertEquals('', $uri->getUserInfo());
        $this->assertEquals('site.ru', $uri->getHost());
        $this->assertEquals(8972, $uri->getPort());
        $this->assertEquals('/hello/world', $uri->getPath());
        $this->assertEquals('day=good', $uri->getQuery());
        $this->assertEquals('some-hash', $uri->getFragment());
    }

    /**
     * @return void
     */
    public function testHomeUri(): void
    {
        $uri = 'http://site.ru:8972';
        $_SERVER = $this->parseUrlToServer($uri);
        $request = RequestGlobal::create();
        $uri = $request->getUri();
        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('site.ru:8972', $uri->getAuthority());
        $this->assertEquals('', $uri->getUserInfo());
        $this->assertEquals('site.ru', $uri->getHost());
        $this->assertEquals(8972, $uri->getPort());
        $this->assertEquals('', $uri->getPath());
        $this->assertEquals('', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    /**
     * @return void
     */
    public function testOnlyPathUri(): void
    {
        $uri = 'http://site.ru:8972/hello/world';
        $_SERVER = $this->parseUrlToServer($uri);
        $request = RequestGlobal::create();
        $uri = $request->getUri();
        $this->assertEquals('/hello/world', $uri->getPath());
        $this->assertEquals('', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    /**
     * @return void
     */
    public function testOnlyQueryUri(): void
    {
        $uri = 'http://site.ru:8972?day=good';
        $_SERVER = $this->parseUrlToServer($uri);
        $request = RequestGlobal::create();
        $uri = $request->getUri();
        $this->assertEquals('', $uri->getPath());
        $this->assertEquals('day=good', $uri->getQuery());
        $this->assertEquals('', $uri->getFragment());
    }

    /**
     * @param  string $uri
     * @return array
     */
    private function parseUrlToServer(string $uri): array
    {
        unset($_SERVER['HTTPS'],$_SERVER['PHP_AUTH_USER'],$_SERVER['PHP_AUTH_PW']);

        $map = [
            'host' => 'SERVER_NAME',
            'port' => 'SERVER_PORT',
            'user' => 'PHP_AUTH_USER',
            'pass' => 'PHP_AUTH_PW',
            'path' => 'PATH_INFO',
            'query' => 'QUERY_STRING',
        ];

        $parsed = [];
        $chunks = parse_url($uri);
        $chunks['path'] = $chunks['path'] ?? '';
        $queryString = $chunks['query'] ?? '';

        if ($queryString) {
            $queryString = sprintf(
                '%s#%s',
                $queryString,
                $chunks['fragment'] ?? ''
            );
        }

        $chunks['query'] = trim($queryString, '#');
        unset($chunks['fragment']);

        foreach ($chunks as $key => $value) {
            if ('scheme' === $key
                && 'https' === $value
            ) {
                $parsed['HTTPS'] = $value;
            }
            if (!isset($map[$key])) {
                continue;
            }
            $sk = $map[$key];
            $parsed[$sk] = $value;
        }

        return array_merge($_SERVER, $parsed);
    }
}
