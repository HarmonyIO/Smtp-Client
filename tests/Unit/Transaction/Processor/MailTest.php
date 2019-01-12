<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ServerSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class MailTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var ServerSocket|MockObject $socket */
    private $socket;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var Mail */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->socket           = $this->createMock(ServerSocket::class);
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);

        $this->processor = new Mail(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Envelop(
                new Localhost(),
                new Address('sender@example.com'),
                new Address('receiver1@example.com'),
                new Address('receiver2@example.com')
            )
        );
    }

    public function testProcessSendMailFromFailsWithTransientNegativeCompletion(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<sender@example.com>\r\n", $data);

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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidMailFromAddress::class);

        wait($this->processor->process($buffer));
    }

    public function testProcessSendMailFromFailsWithPermanentNegativeCompletion(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("MAIL FROM:<sender@example.com>\r\n", $data);

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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidMailFromAddress::class);

        wait($this->processor->process($buffer));
    }
}
