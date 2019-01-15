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
use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn\ProcessLogin;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessLoginTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessLogin */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->processor  = new ProcessLogin(
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
                $this->assertSame("AUTH LOGIN\r\n", $data);

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
                $this->assertSame("AUTH LOGIN\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidCredentials::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessThrowsWhenPositiveIntermediateDoesNotStartWithUsernameOrPassword(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("300 success\r\n"))
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH LOGIN\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(UnexpectedReply::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessResultsInCompletedStatusOnValidCredentials(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("300 VXNlcm5hbWU=\r\n"),
                new Success("300 UGFzc3dvcmQ=\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH LOGIN\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("VGhlVXNlcm5hbWU=\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("VGhlUGFzc3dvcmQ=\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::COMPLETED, $status->getValue());
    }
}
