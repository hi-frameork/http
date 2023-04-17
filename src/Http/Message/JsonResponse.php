<?php

namespace Hi\Http\Message;

class JsonResponse extends Response
{
    public function __construct($data, int $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json';

        parent::__construct($status, $headers, json_encode($data));
    }
}