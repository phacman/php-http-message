<?php

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhacMan\HttpMessage\RequestGlobal;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$request = RequestGlobal::create();
$uri = $request->getUri();

$data = [
    'method' => $request->getMethod(),
    'headers' => $request->getHeaders(),
    'custom-header' => $request->getHeaderLine('x-custom-header'),
    'protocol_version' => $request->getProtocolVersion(),
    'request_target' => $request->getRequestTarget(),
    'body_contents' => $request->getBody()->getContents(),
    'uri' => [
        'scheme' => $uri->getScheme(),
        'authority' => $uri->getAuthority(),
        'userinfo' => $uri->getUserInfo(),
        'host' => $uri->getHost(),
        'port' => $uri->getPort(),
        'path' => $uri->getPath(),
        'query' => $uri->getQuery(),
        'fragment' => $uri->getFragment(),
    ],
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, 128 | 256);
exit(0);
