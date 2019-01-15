<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\ExtensionNegotiation;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessEhlo;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessEhloTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessEhlo */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->processor  = new ProcessEhlo(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Localhost()
        );
    }

    public function testProcessThrowsOnErrorReply(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessFallsBackToHeloWhenEhloIsNotSupported(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::SEND_HELO, $status->getValue());
    }

    public function testProcessResultsInCompletedStatusWhenEhloIsSupportedButNoExtensionsAreAvailable(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 error\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }

    public function testProcessResultsInProcessingExtensionsStatusWhenEhloIsSupported(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200-error\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("EHLO [127.0.0.1]\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::PROCESSING_EXTENSION_LIST, $status->getValue());
    }
}
