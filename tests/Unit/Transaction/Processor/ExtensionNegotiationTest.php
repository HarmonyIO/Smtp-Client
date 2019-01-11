<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Promise;
use Amp\Socket\Socket as ServerSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ExtensionNegotiationTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var ServerSocket|MockObject $socket */
    private $socket;

    /** @var Builder|MockObject $extensionFactory */
    private $extensionFactory;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ExtensionNegotiation */
    private $processor;

    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->socket           = $this->createMock(ServerSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->processor        = new ExtensionNegotiation(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Localhost(),
            new Collection($this->extensionFactory)
        );
    }

    public function testProcessStartsWithSendingEhlo(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->expectException(TransmissionChannelClosed::class);

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessEhloResponseThrowsOnTransientNegativeCompletionResponse(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->expectException(TransmissionChannelClosed::class);

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessEhloResponseFallsBackToHeloWhenEhloIsNotSupported(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("HELO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessHeloResponseThrowsWhenTransientNegativeCompletionIsReturned(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("HELO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process($buffer));
    }

    public function testProcessHeloResponseThrowsWhenPermanentNegativeCompletionIsReturned(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("HELO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process($buffer));
    }

    public function testProcessEhloSupportedWithoutExtensions(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessEhloSupportedWithSingleExtension(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200 AUTH LOGIN\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessEhloSupportedWithMultipleExtensions(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-AUTH LOGIN\r\n"),
                new Success("200 EXTENSION\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testProcessExtensionListThrowsWhenTransientNegativeCompletionIsReturned(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-AUTH LOGIN\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process($buffer));
    }

    public function testProcessExtensionListThrowsWhenPermanentNegativeCompletionIsReturned(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function ($data): Promise {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-AUTH LOGIN\r\n"),
                new Success("500 error\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process($buffer));
    }
}
