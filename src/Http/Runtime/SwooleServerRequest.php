<?php

namespace Hi\Http\Runtime;

use Swoole\Http\Request;

class SwooleServerRequestFactory
{
    /**
     * 返回包装客户端请求参数 ServerRequest 对象
     */
    protected function createServerRequest(Request $request): ServerRequest
    {
        $rawBody = $request->rawContent();

        return new ServerRequest(
            $request->server,
            $this->processUploadFiles($request->files ?? []),
            $request->cookie ?? [],
            $request->get ?? [],
            $this->createStreamBody($rawBody),
            $request->server['request_method'],
            $request->server['path_info'],
            $request->header ?? [],
            $this->parseBody($request->header['content-type'] ?? '', $rawBody ?? $request->post),
            trim(strstr($request->server['server_protocol'], '/'), '/')
        );
    }

    protected function processUploadFiles(array $files): array
    {
        $uploadFiles = [];

        foreach ($files as $file) {
            if (is_array($file['error'])) {
                throw new Exception('不支持以 key 数组方式上传文件', 400);
            }
            $uploadFiles[] = new UploadedFile($file['tmp_name'], $file['size'], $file['error'], $file['name'], $file['type']);
        }

        return $uploadFiles;
    }

    protected function createStreamBody(string $content)
    {
        $body = new Stream('php://memory', 'r+b');
        $body->write($content);
        return $body;
    }

    protected function parseBody($contentType, $body): array
    {
        if (is_array($body)) {
            return $body;
        }

        // 解析内容类型
        $parts       = explode(';', $contentType);
        $contentType = trim($parts[0] ?? '');

        switch ($contentType) {
            case 'application/json':
                return json_decode($body, true);
                break;

            case 'application/x-www-form-urlencoded':
                parse_str($body, $result);
                return $result;
                break;
        }

        return [];
    }
}
