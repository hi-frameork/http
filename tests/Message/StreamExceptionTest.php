<?php

namespace Hi\Http\Tests\Message;

use Hi\Helpers\Exceptions\RuntimeException;
use Hi\Helpers\StatusCode;
use Hi\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class StreamExceptionTest extends TestCase
{
    public function testInvalidFileStream()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(StatusCode::E_500000);
        new Stream('_');
    }
}
