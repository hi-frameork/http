<?php

declare(strict_types=1);

namespace Hi\Tests\Http\Message;

use Hi\Http\Message\Stream;
use InvalidArgumentException;
use Phar;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class SteamTest extends TestCase
{
    /**
     * @var string
     */
    protected $tmpFile;

    /**
     * @var resource
     */
    protected $defaultResource;

    /**
     * @var Stream
     */
    protected Stream $stream;

    protected function setUp(): void
    {
        $this->tmpFile         = tempnam(sys_get_temp_dir(), 'hi-http');
        $this->defaultResource = fopen('php://temp', 'r');
        $this->stream          = new Stream('php://temp', 'w+b');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }
    }

    public function testGetDefault(): void
    {
        $stream = new Stream();
        $this->assertSame(0, $stream->tell());
        $this->assertFalse($stream->eof());
        $this->assertTrue($stream->isSeekable());
        $this->assertTrue($stream->isReadable());
        $this->assertTrue($stream->isWritable());
        $this->assertSame(0, $stream->getSize());
        $this->assertSame('', $stream->getContents());
        $this->assertSame($this->stream->getMetadata(), $stream->getMetadata());
    }

    public function testGetMetadata(): void
    {
        $this->assertSame('PHP', $this->stream->getMetadata('wrapper_type'));
        $this->assertSame('TEMP', $this->stream->getMetadata('stream_type'));
        $this->assertSame('w+b', $this->stream->getMetadata('mode'));
        $this->assertSame(0, $this->stream->getMetadata('unread_bytes'));
        $this->assertSame(true, $this->stream->getMetadata('seekable'));
        $this->assertSame('php://temp', $this->stream->getMetadata('uri'));
        $this->assertSame(null, $this->stream->getMetadata('not-exist-key'));
    }

    public function testIsWriteableAndWriteAndToString(): void
    {
        $this->assertTrue($this->stream->isWritable());
        $this->stream->write($content = 'content');
        $this->assertSame($content, (string) $this->stream);
    }

    public function testCreateResourceThrowExceptionForStreamCannotBeOpened(): void
    {
        $this->expectException(RuntimeException::class);
        new Stream('php://fail');
    }

    public function testIsEmptyStringForNotReadable(): void
    {
        $stream = new Stream('php://output', 'w');
        $this->assertSame('', (string) $stream);
    }

    public function testCloseAndGetSizeIfUnknown(): void
    {
        $this->stream->close();
        $this->assertNull($this->stream->getSize());
    }

    public function testDetach(): void
    {
        $stream = new Stream($this->defaultResource);
        $this->assertSame(stream_get_meta_data($this->defaultResource), stream_get_meta_data($stream->detach()));
        $this->assertNull($stream->getSize());
    }

    public function testWriteAndGetSizeAndDetach(): void
    {
        $this->assertSame(0, $this->stream->getSize());

        $this->stream->write('a');
        $this->assertSame(1, $this->stream->getSize());

        $this->stream->write('b');
        $this->assertSame(2, $this->stream->getSize());

        $this->stream->detach();
        $this->assertNull($this->stream->getSize());
    }

    public function testIsReadableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertTrue($stream->isReadable());
    }

    public function testIsReadableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $this->assertFalse($stream->isReadable());
    }

    public function testIsWriteableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $this->assertTrue($stream->isWritable());
    }

    public function testIsWriteableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertFalse($stream->isWritable());
    }

    public function testIsSeekableReturnTrue(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->assertTrue($stream->isSeekable());
    }

    public function testIsSeekableReturnFalse(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $stream->close();
        $this->assertFalse($stream->isSeekable());
    }

    public function testReadThrowExceptionForStreamIsNotReadable(): void
    {
        $stream = new Stream($this->tmpFile, 'w');
        $this->expectException(RuntimeException::class);
        $stream->read(1);
    }

    public function testWriteThrowExceptionForStreamIsNotWriteable(): void
    {
        $stream = new Stream($this->tmpFile, 'r');
        $this->expectException(RuntimeException::class);
        $stream->write('content');
    }

    public function testSeekThrowExceptionForInvalidResource(): void
    {
        $this->stream->close();
        $this->expectException(RuntimeException::class);
        $this->stream->seek(1);
    }

    public function testTellThrowExceptionForInvalidResource(): void
    {
        $this->stream->close();
        $this->expectException(RuntimeException::class);
        $this->stream->tell();
    }

    public function testReadThrowExceptionForInvalidResource(): void
    {
        $this->stream->close();
        $this->expectException(RuntimeException::class);
        $this->stream->read(1);
    }

    public function testGetContentThrowExceptionForInvalidResource(): void
    {
        $this->stream->close();
        $this->expectException(RuntimeException::class);
        $this->stream->getContents();
    }

    public function testConstructThrowExceptionForInvalidResource(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Stream(stream_context_create(['phar' => ['compress' => Phar::GZ]]));
    }

    public function testCacheMetadataForCoverage(): void
    {
        $this->assertSame(0, $this->stream->getSize());
        $this->assertSame(0, $this->stream->getSize());

        $this->assertTrue($this->stream->isReadable());
        $this->assertTrue($this->stream->isReadable());

        $this->assertTrue($this->stream->isSeekable());
        $this->assertTrue($this->stream->isSeekable());

        $this->assertTrue($this->stream->isWritable());
        $this->assertTrue($this->stream->isWritable());
    }
}
