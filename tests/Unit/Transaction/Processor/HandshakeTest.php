<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;

class HandshakeTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var Handshake */
    private $processor;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
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
