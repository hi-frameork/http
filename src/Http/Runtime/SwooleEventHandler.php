<?php

namespace Hi\Http\Runtime;

use Hi\Http\Exception;
use HttpSoft\Message\ServerRequest;
use HttpSoft\Message\Stream;
use HttpSoft\Message\UploadedFile;
use Swoole\Http\Request;
use Swoole\Http\Response;

class SwooleEventHandler
{
    public function onRequest(Request $request, Response $response): void
    {
        $serverRequest = new SwooleServerRequest($request);

        $response->end('hi-framework');
    }
}
