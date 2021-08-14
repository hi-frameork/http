<?php

namespace Hi\Http;

use Hi\Helpers\Json;
use Swoole\Coroutine\Http\Client as SwooleClient;

class Client
{
    /**
     * 发课程基础数据发送 post 请求
     *
     * @return array
     */
    public static function post($addr, $path, $data)
    {
        // 发送请求
        $client = new SwooleClient($addr['host'], $addr['port']);
        $client->setHeaders(['Content-Type' => 'application/json']);
        $client->post($path, Json::encode($data));
        $client->close();

        if ($client->statusCode != 200) {
            throw new Exception('HTTP 请求失败', -1, ['error' => $client->errMsg, 'body' => $client->body]);
        }

        return Json::decode($client->body, true);
    }
}
