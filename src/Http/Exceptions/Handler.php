<?php

declare(strict_types=1);

namespace Hi\Http\Exceptions;

use Hi\Http\Exception;
use Hi\Http\Message\Response;
use Throwable;

class Handler
{
    // FIXME add report logic
    public static function reportAndprepareResponse(Throwable $e)
    {
        // 除非由业务指定 http statusCode
        // 否则抛出异常时一律使用 500 作为 statusCode
        $status = 500;

        $data = [
            'message' => $e->getMessage(),
            'code'    => $e->getCode(),
        ];

        // 获取异常所携带的额外信息
        if ($e instanceof Exception) {
            $status           = $e->getStatusCode();
            $data['addition'] = $e->getAddition();
        }

        $response = (new Response())
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json')
        ;

        $response->getBody()->write(Json::encode($data));

        return $response;
    }
}
