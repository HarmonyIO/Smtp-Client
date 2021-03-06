<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\LogIn;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn\ProcessCramMd5;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessCramMd5Test extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessCramMd5 */
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
        $this->processor  = new ProcessCramMd5(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Authentication('TheUsername', 'ThePassword')
        );
    }

    public function testProcessThrowsOnUnknownError(): void
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
                $this->assertSame("AUTH CRAM-MD5\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnInvalidCredentials(): void
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
                $this->assertSame("AUTH CRAM-MD5\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidCredentials::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnUnknownErrorAfterSendingTheResponse(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("300 success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH CRAM-MD5\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("VGhlVXNlcm5hbWUgYmI5ZDRlNWY5YzVhOTQ4ZDczMmFhYThkYjA1MzkyOTY=\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnInvalidCredentialsAfterSendingTheResponse(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("300 success\r\n"),
                new Success("500 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH CRAM-MD5\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("VGhlVXNlcm5hbWUgYmI5ZDRlNWY5YzVhOTQ4ZDczMmFhYThkYjA1MzkyOTY=\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidCredentials::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsInCompletedStatusOnValidCredentials(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("300 VXNlcm5hbWU=\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH CRAM-MD5\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("VGhlVXNlcm5hbWUgMjNhMmRmY2NhZDg3ZjhkYjVjODUxOWU3MDI4ZmYxMGU=\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }
}
