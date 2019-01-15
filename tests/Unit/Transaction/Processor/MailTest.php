<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Envelop\Header;
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

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var Mail */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger     = new Output(new Level(Level::NONE));
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->smtpSocket = $this->createMock(SmtpSocket::class);

        $envelop = (new Envelop(
            new Localhost(),
            new Envelop\Address('sender@example.com'),
            new Address('receiver@example.com')
        ))
            ->addHeader(new Header('Foo', 'Bar'))
            ->body('Example body')
        ;

        $this->processor = new Mail(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $envelop
        );
    }

    public function testProcessProcessesEntireContent(): void
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
                $this->assertSame("RCPT TO:<receiver@example.com>\r\n", $data);

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
                $this->assertStringStartsWith('Message-ID:', $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(4))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertStringStartsWith('Date:', $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(5))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("From:<sender@example.com>\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(6))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("To:<receiver@example.com>\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(7))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("Foo:Bar\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(8))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(9))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("Example body\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(10))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame(".\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(11))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200 success\r\n"),
                new Success("200 success\r\n"),
                new Success("300 success\r\n"),
                new Success("200 success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
    }
}
