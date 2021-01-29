<?php

declare(strict_types=1);

namespace Hi\Http\Message\Stream;

use Hi\Http\Message\Stream;
use function stream_get_contents;

class Input extends Stream
{
    private $data = '';

    private $eof = false;

    public function __construct()
    {
        parent::__construct('php://input', 'rb');
    }

    public function __toString(): string
    {
        if ($this->eof) {
            return $this->data;
        }

        $this->getContents();

        return $this->data;
    }

    public function getContents($length = -1): string
    {
        if ($this->eof) {
            return $this->data;
        }

        $data = stream_get_contents($this->handle, $length);
        $this->data = $data;

        if ($length === -1 || $this->eof()) {
            $this->eof = true;
        }

        return $this->data;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function read($length): string
    {
        $data = parent::read($length);

        if ($this->eof) {
            $this->data = $data;
        }

        if ($this->eof()) {
            $this->eof = true;
        }

        return $data;
    }
}
