<?php

namespace Hi\Http;

use Hi\Helpers\Json;
use Swoole\Coroutine\Http\Client;

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
        $client = new Client($addr['host'], $addr['port']);
        $client->setHeaders(['Content-Type' => 'application/json']);
        $client->post($path, Json::encode($data));
        $client->close();

        if ($client->statusCode != 200) {
            throw new Exception('请求失败');
        }

        return Json::decode($client->body, true);
    }
}
