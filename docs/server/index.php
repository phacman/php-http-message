<?php

use PhacMan\HttpMessage\ServerRequestGlobal;

require_once dirname(__DIR__, 2).'/vendor/autoload.php';

$request = ServerRequestGlobal::create();
$data = $_FILES
    ? ['uploaded_files' => $request->withUploadedFiles($_FILES)->getUploadedFiles()]
    : ['parsed_body' => $request->getParsedBody()];
$data['custom-header'] = $request->getHeaderLine('x-custom-header');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data, 128|256);
exit(0);
