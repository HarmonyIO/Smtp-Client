<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class LogInTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var Builder|MockObject $socket */
    private $extensionFactory;

    /** @var Collection */
    private $extensions;

    /** @var LogIn */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->socket           = $this->createMock(ClientSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->extensions       = new Collection($this->extensionFactory);
        $this->processor        = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $this->extensions,
            new Authentication('TheUsername', 'ThePassword')
        );
    }

    public function testProcessBailsOutWhenAuthenticationIsNotSet(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $processor = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Collection($this->extensionFactory)
        );

        $this->assertNull($processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessBailsOutWhenServerDoesNotSupportAuthentication(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $this->assertNull($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessRunsPlainAuthentication(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('PLAIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('200 success')
        ;

        $this->extensions->enable($reply);

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("AUTH PLAIN AFRoZVVzZXJuYW1lAFRoZVBhc3N3b3Jk\r\n", $data);

                return new Success();
            })
        ;

        $this->expectException(InvalidCredentials::class);

        wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessRunsLoginAuthentication(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('LOGIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('200 success')
        ;

        $this->extensions->enable($reply);

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

    public function testProcessRunsCramMd5Authentication(): void
    {
        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('200 success')
        ;

        $this->extensions->enable($reply);

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
}
