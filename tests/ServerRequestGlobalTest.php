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

use Generator;
use PhacMan\HttpMessage\ServerRequestGlobal;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhacMan\HttpMessage\ServerRequestGlobal
 */
class ServerRequestGlobalTest extends TestCase
{
    /**
     * @return void
     */
    public function testGetParams(): void
    {
        $_SERVER = $this->getApiServer('text/html; charset=utf-8');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['QUERY_STRING'] = 'hello=world&day=good';
        $request = ServerRequestGlobal::create();
        $qs = $request->getQueryParams();
        $this->assertIsArray($qs);
        $this->assertEquals($_GET, $qs);
        $this->assertEquals('GET', $request->getMethod());
    }

    /**
     * @return void
     */
    public function testCookieParams(): void
    {
        $_SERVER = $this->getApiServer('text/html; charset=utf-8');
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_COOKIE = ['hello' => 'world'];
        $request = ServerRequestGlobal::create();
        $cookie = $request->getCookieParams();
        $this->assertIsArray($cookie);
        $this->assertEquals($_COOKIE, $cookie);
        $this->assertEquals('GET', $request->getMethod());
    }

    /**
     * @dataProvider fileUploadedDataProvider
     * @param  string $files
     * @return void
     */
    public function testFileUploaded(string $files): void
    {
        $_SERVER = $this->getApiServer('multipart/form-data; boundary=----WebKitFormBoundary');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_FILES = require_once __DIR__.'/Resources/'.$files;
        $request = ServerRequestGlobal::create();
        $uploadedFiles = $request->getUploadedFiles();
        $current = current($uploadedFiles);
        $this->assertIsArray($uploadedFiles);
        $this->assertIsObject($current);
        $this->assertEquals('image/png', $current->getClientMediaType());
        $this->assertEquals(0, $current->getError());
        $this->assertTrue(\is_int($current->getSize()));
        $this->assertEquals('POST', $request->getMethod());
    }

    /**
     * @return void
     */
    public function testPostJson(): void
    {
        $_SERVER = $this->getApiServer('application/json');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = ['hello' => 'world'];
        $request = ServerRequestGlobal::create();
        $parsedBody = $request->getParsedBody();
        $this->assertIsArray($parsedBody);
        $this->assertEquals($_POST, $parsedBody);
        $this->assertEquals('POST', $request->getMethod());
    }

    /**
     * @return void
     */
    public function testPostFormUrlEncoded(): void
    {
        $_SERVER = $this->getApiServer('application/x-www-form-urlencoded');
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $data = 'client_id=keycloak&username=keycloak&password=keycloak&grant_type=password&scope=openid';
        parse_str($data, $_POST);
        $request = ServerRequestGlobal::create();
        $parsedBody = $request->getParsedBody();
        $this->assertIsArray($parsedBody);
        $this->assertEquals($_POST, $parsedBody);
        $this->assertEquals('POST', $request->getMethod());
    }

    /**
     * @return void
     */
    public function testPutJson(): void
    {
        $_SERVER = $this->getApiServer('application/json');
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        $_POST = ['hello' => 'world'];
        $request = ServerRequestGlobal::create();
        $parsedBody = $request->getParsedBody();
        $this->assertIsArray($parsedBody);
        $this->assertEquals($_POST, $parsedBody);
        $this->assertEquals('PUT', $request->getMethod());
    }

    /**
     * @return void
     */
    public function testPatchJson(): void
    {
        $_SERVER = $this->getApiServer('application/json');
        $_SERVER['REQUEST_METHOD'] = 'PATCH';
        $_POST = ['hello' => 'world'];
        $request = ServerRequestGlobal::create();
        $parsedBody = $request->getParsedBody();
        $this->assertIsArray($parsedBody);
        $this->assertEquals($_POST, $parsedBody);
        $this->assertEquals('PATCH', $request->getMethod());
    }

    /**
     * @return Generator
     */
    public static function fileUploadedDataProvider(): Generator
    {
        yield [
            'files' => 'mp_form_01.php',
        ];

        yield [
            'files' => 'mp_form_02.php',
        ];
    }

    private function getApiServer(string $ct): array
    {
        $api = [
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 8972,
            'PATH_INFO' => '/api',
            'HTTP_CONTENT_TYPE' => $ct,
        ];

        return array_merge($_SERVER, $api);
    }
}
