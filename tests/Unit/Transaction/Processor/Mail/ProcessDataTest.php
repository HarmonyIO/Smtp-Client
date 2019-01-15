<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\Mail;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessData;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessDataTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessData */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->processor  = new ProcessData(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket)
        );
    }

    public function testProcessThrowsOnTransientError(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(DataNotAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentError(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(DataNotAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsSendingHeadersStatus(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("300 success\r\n"))
        ;

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::SENDING_HEADERS, $status->getValue());
    }
}
