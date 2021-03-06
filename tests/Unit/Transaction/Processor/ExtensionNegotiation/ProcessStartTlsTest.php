<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\ExtensionNegotiation;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\CouldNotUpgradeConnection;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessStartTls;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessStartTlsTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessStartTls */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->processor  = new ProcessStartTls(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket)
        );
    }

    public function testProcessThrowsOnTransientErrorReply(): void
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
                $this->assertSame("STARTTLS\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(CouldNotUpgradeConnection::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorReply(): void
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
                $this->assertSame("STARTTLS\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(CouldNotUpgradeConnection::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsInProcessingExtensionsStatusWhenStartTlsIsSupported(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("STARTTLS\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->once())
            ->method('enableCrypto')
            ->willReturn(new Success())
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::START_TLS_PROCESS, $status->getValue());
    }
}
