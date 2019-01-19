<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Envelop\Header;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\Pipelining;
use HarmonyIO\SmtpClient\Transaction\Processor\MailPipeining;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class MailPipeliningTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var MailPipeining */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

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

        $this->processor = new MailPipeining(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $envelop,
            new Collection($this->createMock(Builder::class))
        );
    }

    public function testProcessBailsOutWhenPipeLiningExtensionIsNotEnabled(): void
    {
        $this->socket
            ->expects($this->never())
            ->method('write')
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->processor->process($buffer));
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

        $envelop = (new Envelop(
            new Localhost(),
            new Envelop\Address('sender@example.com'),
            new Address('receiver@example.com')
        ))
            ->addHeader(new Header('Foo', 'Bar'))
            ->body('Example body')
        ;

        /** @var Builder|MockObject $extensionFactory */
        $extensionFactory = $this->createMock(Builder::class);

        $extensionFactory
            ->method('build')
            ->willReturn(new Pipelining())
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('PIPELINING')
        ;

        $collection = new Collection($extensionFactory);

        $collection->enable($reply);

        $processor = new MailPipeining(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $envelop,
            $collection
        );

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($processor->process($buffer));
    }
}
