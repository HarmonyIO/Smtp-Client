<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\TransactionFailed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

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

    public function testAwaitingGreetingThrowsOnTransientNegativeCompletion(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->expectException(TransactionFailed::class);

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testAwaitingGreetingCompletesTransaction(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testAwaitingGreetingMovesToAwaitingSingleLineBanner(): void
    {
        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("200-error\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("200 banner line 1\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testAwaitingGreetingMovesToAwaitingMultiLineBanner(): void
    {
        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("200-success\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("200-banner line 1\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(2))
            ->method('read')
            ->willReturn(new Success("200 banner line 2\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }

    public function testAwaitingBannerThrowsOnTransientNegativeCompletion(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->expectException(TransactionFailed::class);

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }
}
