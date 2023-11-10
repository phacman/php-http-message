<?php

/*
 * This file is part of HttpMessage package.
 *
 * (c) Pavel Vasin <phacman@yandex.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhacMan\HttpMessage\ServerRequestGlobal;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$request = ServerRequestGlobal::create();
$data = $_FILES
    ? ['uploaded_files' => $request->withUploadedFiles($_FILES)->getUploadedFiles()]
    : ['parsed_body' => $request->getParsedBody()];
$data['custom-header'] = $request->getHeaderLine('x-custom-header');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, 128 | 256);
exit(0);
