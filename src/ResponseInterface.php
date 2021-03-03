<?php declare(strict_types=1);

namespace Hi\Http;

interface ResponseInterface
{
    public function setContent(string $content): ResponseInterface;

    public function getContent(): string;

    public function send();
}
