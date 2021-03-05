<?php

namespace Hi\Http\Tests\Message;

use RuntimeException;
use Hi\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class StreamExceptionTest extends TestCase
{
    public function testInvalidFileStream()
    {
        $this->expectException(RuntimeException::class);
        new Stream('_');
    }
}
