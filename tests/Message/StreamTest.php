<?php

namespace Hi\Http\Tests\Message;

use Hi\Http\Message\Stream;
use PHPUnit\Framework\TestCase;

class StreamTest extends TestCase
{
    /**
     * @var Stream
     */
    protected $stream;

    protected function setUp(): void
    {
        $this->stream = new Stream('php://memory', 'wb+');
        parent::setUp();
    }

    public function testStreamStatus()
    {
        $this->assertTrue($this->stream->isReadable());
        $this->assertTrue($this->stream->isWritable());
    }

    public function testToStringRetrievesFullContentsOfStream()
    {
        $data = uniqid();
        $this->assertEquals(strlen($data), $this->stream->write($data));
        $this->assertEquals($data, (string) $this->stream);
    }

    public function testGetContentsOfStream()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertEmpty($this->stream->getContents());
        $this->stream->rewind();
        $this->assertEquals($message, (string) $this->stream);
        $this->stream->rewind();
        $this->assertEquals($this->stream->getContents(), (string) $this->stream);
        $this->assertTrue($this->stream->eof());
    }

    public function testStreamSize()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertEquals(7, $this->stream->getSize());
    }

    public function testStreamReadAndWrite()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertFalse($this->stream->eof());
        $this->assertEquals(7, $this->stream->tell());
        $this->stream->seek(4);
        $this->assertEquals('ba', $this->stream->read(2));
        $this->assertEquals('r', $this->stream->read(1));
    }

    public function testStreamEOF()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->stream->seek(0);
        $this->assertFalse($this->stream->eof());
        $this->stream->getContents();
        $this->assertTrue($this->stream->eof());
    }

    public function testStreamClose()
    {
        $message = 'foo bar';
        $this->stream->write($message);
        $this->assertEquals(7, $this->stream->getSize());
        $this->stream->close();
        $this->assertFalse($this->stream->isWritable());
    }
}
