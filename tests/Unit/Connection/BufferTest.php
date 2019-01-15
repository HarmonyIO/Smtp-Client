<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Exception\ConnectionClosedUnexpectedly;
use HarmonyIO\SmtpClient\Log\Output;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class BufferTest extends TestCase
{
    /** @var SmtpSocket|MockObject */
    private $socket;

    /** @var Output|MockObject */
    private $logger;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->socket = $this->createMock(SmtpSocket::class);
        $this->logger = $this->createMock(Output::class);
    }

    public function testReadLineReturnsNullWhenThereIsNothingMoreToReadFromTheSocketAndTheSocketIsClosedOnCall(): void
    {
        $this->socket
            ->method('read')
            ->willReturnOnConsecutiveCalls(new Success("foo\r\n"), new Success(null))
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        $this->assertSame('foo', $buffer->readLine());
        $this->assertNull($buffer->readLine());
    }

    public function testReadLineThrowsWhenTheSocketIsClosedPrematurely(): void
    {
        $this->socket
            ->method('read')
            ->willReturnOnConsecutiveCalls(new Success('foo'), new Success(null))
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        $this->expectException(ConnectionClosedUnexpectedly::class);
        $this->expectExceptionMessage('The connection closed while processing an SMTP reply.');

        wait($buffer->readLine());
    }

    public function testReadLineLogsNoticeWhenLineLengthExceedsTheSmtpRfcLineLength(): void
    {
        $line = str_repeat('x', 513);

        $this->socket
            ->method('read')
            ->willReturn(new Success($line . "\r\n"))
        ;

        $this->logger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message, array $context) use ($line): void {
                $this->assertSame('Line exceeds RFC SMTP line length. We process it anyway,', $message);
                $this->assertArrayHasKey('line', $context);
                $this->assertSame($line, $context['line']);
            })
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        wait($buffer->readLine());
    }

    public function testReadLineCorrectlyHandlesASingleLineInASinglePacket(): void
    {
        $this->socket
            ->method('read')
            ->willReturn(new Success("foobar\r\n"))
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        $this->assertSame('foobar', $buffer->readLine());
    }

    public function testReadLineCorrectlyHandlesMultipleLinesInASinglePacket(): void
    {
        $this->socket
            ->method('read')
            ->willReturn(new Success("foo\r\nbar\r\n"))
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        $this->assertSame('foo', $buffer->readLine());
        $this->assertSame('bar', $buffer->readLine());
    }

    public function testReadLineCorrectlyHandlesALineSplitUpOverMultiplePackets(): void
    {
        $this->socket
            ->method('read')
            ->willReturnOnConsecutiveCalls(new Success("foo"), new Success("bar\r\n"))
        ;

        $buffer = new Buffer($this->socket, $this->logger);

        $this->assertSame('foobar', $buffer->readLine());
    }
}
