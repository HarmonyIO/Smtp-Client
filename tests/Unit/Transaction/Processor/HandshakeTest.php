<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;

class HandshakeTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var Handshake */
    private $processor;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->processor  = new Handshake(new Factory(), $this->logger);
    }

    public function testProcessProcessesEntireHandShakeWithoutBanner(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->assertNull($this->processor->process($buffer));
    }

    public function testProcessProcessesEntireHandShakeWithBanner(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->assertNull($this->processor->process($buffer));
    }
}
