<?php declare(strict_types=1);

namespace Hi\Http\Runtime;

use Hi\Http\Exceptions\InvalidArgumentException;
use Hi\Http\Message\UploadedFile;
use Hi\Helpers\Json;
use Hi\Http\Message\Stream\Memory;

trait RuntimeTrait
{
    protected function processUploadFiles(array $files): array
    {
        $uploadFiles = [];

        foreach ($files as $file) {
            if (is_array($file['error'])) {
                throw new InvalidArgumentException('不支持以 key 数组方式上传文件', 400);
            }
            $uploadFiles[] = new UploadedFile($file['tmp_name'], $file['size'], $file['error'], $file['name'], $file['type']);
        }

        return $uploadFiles;
    }

    protected function parseBody($contentType, $body)
    {
        if (is_array($body)) {
            return $body;
        }

        // 解析内容类型
        $parts       = explode(';', $contentType);
        $contentType = trim($parts[0] ?? '');

        switch ($contentType) {
            case 'application/json':
                return Json::decode($body, true);
                break;

            case 'application/x-www-form-urlencoded':
                parse_str($body, $result);
                return $result;
                break;
        }

        return $body;
    }

    protected function createStreamBody(string $content)
    {
        $body = new Memory('r+b');
        $body->write($content);
        return $body;
    }
}
