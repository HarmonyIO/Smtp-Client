<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\Mail;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;
use HarmonyIO\SmtpClient\Exception\Smtp\NoRecipientsAccepted;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessPipelining;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessPipeliningTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessPipelining */
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
        $this->processor  = new ProcessPipelining(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Address('from@example.com', 'From Example'),
            new Address('to@example.com', 'To Example')
        );
    }

    public function testProcessThrowsOnTransientErrorForMailFromAddress(): void
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
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidMailFromAddress::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorForMailFromAddress(): void
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
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidMailFromAddress::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnTransientErrorWhenNoRecipientHaveBeenAccepted(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(NoRecipientsAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorWhenNoRecipientHaveBeenAccepted(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("500 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(NoRecipientsAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnTransientErrorWhenDataWasNotAccepted(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("200 success\r\n"),
                new Success("400 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(DataNotAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsOnPermanentErrorWhenDataWasNotAcceptedAccepted(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("200 success\r\n"),
                new Success("500 error\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<from@example.com> From Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("RCPT TO:<to@example.com> To Example\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("DATA\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(3))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(DataNotAccepted::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsInSendingHeadersStatusWhenEverythingIsValid(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("200 success\r\n"),
                new Success("300 success\r\n")
            )
        ;

        $this->socket
            ->method('write')
            ->willReturn(new Success())
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::SENDING_HEADERS, $status->getValue());
    }
}
