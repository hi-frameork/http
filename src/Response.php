<?php declare(strict_types=1);

namespace Hi\Http;

class Response implements ResponseInterface
{
    /**
     * @var string
     */
    protected $content = '';

    public function setContent(string $content): ResponseInterface
    {
        $this->content = $content;
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function send()
    {
        echo $this->content;
    }
}
