<?php

declare(strict_types=1);

namespace Hi\Tests\Http\Message;

use Hi\Http\Message\Message as BaseMessage;
use Hi\Tests\Http\Message\TestAsset\Message;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

class MessageTest extends TestCase
{
    protected BaseMessage $message;

    protected function setUp(): void
    {
        $this->message = new Message();
    }

    public function testGetDefault(): void
    {
        $this->assertInstanceOf(StreamInterface::class, $this->message->getBody());
        $this->assertSame([], $this->message->getHeaders());
        $this->assertSame(Message::DEFAULT_PROTOCOL_VERSION, $this->message->getProtocolVersion());
    }

    public function testWithProtocolVersion(): void
    {
        $message = $this->message->withProtocolVersion('2');
        $this->assertNotSame($message, $this->message);
        $this->assertSame('2', $message->getProtocolVersion());
    }

    public function testWithProtocolVersionHasNotBeenChangedNotClone()
    {
        $message = $this->message->withProtocolVersion(Message::DEFAULT_PROTOCOL_VERSION);
        $this->assertSame($message, $this->message);
        $this->assertSame(Message::DEFAULT_PROTOCOL_VERSION, $message->getProtocolVersion());
    }

    public function testBodyPassingInConstructorStreamInterface(): void
    {
        $stream = $this->createMock(StreamInterface::class);
        $this->assertSame($stream, (new Message($stream))->getBody());
    }

    public function testWithBody(): void
    {
        $stream = $this->createMock(StreamInterface::class);

        $message1 = $this->message->withBody($stream);
        $this->assertNotSame($message1, $this->message);
        $this->assertSame($stream, $message1->getBody());

        $message2 = $message1->withBody($stream);
        $this->assertSame($message2, $message1);
        $this->assertSame($stream, $message2->getBody());
    }

    public function testWithAndGetHeader()
    {
        $message1 = $this->message->withHeader('Name', 'Value1');
        $this->assertNotSame($message1, $this->message);
        $this->assertSame(['Value1'], $message1->getHeader('name'));

        $message2 = $message1->withHeader('Name', 'Value2');
        $this->assertNotSame($message2, $message1);
        $this->assertSame(['Value2'], $message2->getHeader('Name'));
    }

    public function testWithAndGetHeaders()
    {
        $message1 = $this->message->withHeader('Name', 'Value');
        $this->assertNotSame($message1, $this->message);
        $this->assertSame(['Name' => ['Value']], $message1->getHeaders());
    }

    public function testWithAddedAndGetHeaders(): void
    {
        $firstMessage = $this->message->withAddedHeader('Name', 'FirstValue');
        $this->assertNotSame($this->message, $firstMessage);
        $this->assertSame(['Name' => ['FirstValue']], $firstMessage->getHeaders());

        $secondMessage = $firstMessage->withAddedHeader('Name', 'SecondValue');
        $this->assertNotSame($firstMessage, $secondMessage);
        $this->assertSame(['Name' => ['FirstValue', 'SecondValue']], $secondMessage->getHeaders());
    }

    public function testWithoutAndGetHeaders()
    {
        $message1 = $this->message->withHeader('Name', 'Value');
        $this->assertNotSame($message1, $this->message);
        $this->assertSame(['Name' => ['Value']], $message1->getHeaders());

        $message2 = $message1->withoutHeader('Name');
        $this->assertNotSame($message2, $message1);
        $this->assertSame([], $message2->getHeaders());

        $message3 = $message2->withoutHeader('Name');
        $this->assertSame($message3, $message2);
        $this->assertSame([], $message3->getHeaders());
    }

    public function testHasHeaderForFalse()
    {
        $this->assertFalse($this->message->hasHeader('Name'));
    }

    public function testHasHeaderForTrue()
    {
        $message = $this->message->withHeader('Name', 'Value');
        $this->assertTrue($message->hasHeader('Name'));
        $this->assertNotSame($message, $this->message);
    }

    public function tewtNotExistHeaderReturnEmptyArray()
    {
        $this->assertSame([], $this->message->getHeader('Name'));
    }

    public function testGetHeaderLine()
    {
        $message = $this->message->withHeader('Name', ['Value1', 'Value2']);
        $this->assertNotSame($message, $this->message);
        $this->assertSame('Value1,Value2', $message->getHeaderLine('Name'));
    }

    public function testNotExistHeaderLineReturnEmptyString()
    {
        $this->assertSame('', $this->message->getHeaderLine('Name'));
    }

    public function testWithHeaderThrowExceptionForInvalidHeader(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withHeader([true], 'Value');
    }

    public function testWithProtocolVersionThrowExceptionForUnsupportedProtocolVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->message->withProtocolVersion(['', '']);
    }

    public function testBodyPassingInConstructorThrowExceptionForInvalidBodyType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Message(['']);
    }
}
