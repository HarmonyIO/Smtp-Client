<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\ExtensionNegotiation;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessExtensions;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessExtensionsTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var Builder|MockObject $socket */
    private $extensionFactory;

    /** @var ProcessExtensions */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->processor        = new ProcessExtensions(
            new Factory(),
            $this->logger,
            new Collection($this->extensionFactory)
        );
    }

    public function testProcessThrowsOnTransientErrorReply(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnTransientErrorReplyAfterSuccessfulReply(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorReply(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorReplyAfterSuccessfulReply(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("500 error\r\n")
            )
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessEnablesSingleExtensions(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(new Success("200 success\r\n"))
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }

    public function testProcessEnablesMultipleExtensions(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }
}