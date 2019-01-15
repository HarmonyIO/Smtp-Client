<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\Handshake;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Exception\TransactionFailed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake\ProcessBanner;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Handshake as Status;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessBannerTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ProcessBanner */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->processor  = new ProcessBanner(new Factory(), $this->logger);
    }

    public function testProcessThrowsOnErrorReply(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->expectException(TransactionFailed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsWhenThirdBannerLineResultsInAnErrorReply(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->expectException(TransactionFailed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsInCompletedStatusWhenTheBannerContainsOnlyASingleLine(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }

    public function testProcessResultsInCompletedStatusWhenTheBannerContainsMultipleLines(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }
}
