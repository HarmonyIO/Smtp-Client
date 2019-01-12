<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ServerSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class LogInTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var ServerSocket|MockObject $socket */
    private $socket;

    /** @var Builder|MockObject $extensionFactory */
    private $extensionFactory;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var Collection */
    private $extensions;

    /** @var LogIn */
    private $unauthenticatedProcessor;

    /** @var LogIn */
    private $authenticatedProcessor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->socket           = $this->createMock(ServerSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->extensions       = new Collection($this->extensionFactory);

        $this->unauthenticatedProcessor = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $this->extensions
        );

        $this->authenticatedProcessor = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $this->extensions,
            new Authentication('TheUsername', 'ThePassword')
        );
    }

    public function testProcessBailsOutWhenUnauthenticated(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->unauthenticatedProcessor->process($buffer));
    }

    public function testProcessBailsOutWhenTheAuthExtensionIsNotEnabled(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsPlainLogInRequestAndFailsWithATransientNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('PLAIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsPlainLogInRequestAndFailsWithAPermanentNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('PLAIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidCredentials::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsPlainLogInRequestAndSucceeds(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('PLAIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsLoginLogInRequestAndFailsWithATransientNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('LOGIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsLoginLogInRequestAndFailsWithAPermanentNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('LOGIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidCredentials::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsLoginLogInRequestAndSucceedsWithAPositiveCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('LOGIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsLoginLogInRequestAndSucceedsWithAPositiveIntermediates(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('LOGIN'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
        ;

        $this->extensions->enable($reply);

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

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("334 VXNlcm5hbWU6\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("334 UGFzc3dvcmQ6\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(2))
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsCramMd5LogInRequestAndFailsWithATransientNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsCramMd5LogInRequestAndFailsWithAPermanentNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
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

        $this->smtpSocket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidCredentials::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsCramMd5LogInRequestAndFirstSucceedsThenFailsWithATransientNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
        ;

        $this->extensions->enable($reply);

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
                $this->assertSame("VGhlVXNlcm5hbWUgNTU5YTE1OTc2YjFiZjk0ZmE2NmY4NGUzMWEzOWRmZDI=\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("334 PDQxOTI5NDIzNDEuMTI4Mjg0NzJAc291cmNlZm91ci5hbmRyZXcuY211LmVkdT4=\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("400 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(TransmissionChannelClosed::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessSendsCramMd5LogInRequestAndFirstSucceedsThenFailsWithAPermanentNegativeCompletion(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
        ;

        $this->extensions->enable($reply);

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
                $this->assertSame("VGhlVXNlcm5hbWUgNTU5YTE1OTc2YjFiZjk0ZmE2NmY4NGUzMWEzOWRmZDI=\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("334 PDQxOTI5NDIzNDEuMTI4Mjg0NzJAc291cmNlZm91ci5hbmRyZXcuY211LmVkdT4=\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("500 error\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        $this->expectException(InvalidCredentials::class);

        wait($this->authenticatedProcessor->process($buffer));
    }

    public function testProcessCramMd5LoginSucceeds(): void
    {
        $this->extensionFactory
            ->method('build')
            ->willReturn(new Auth('CRAM-MD5'))
        ;

        /** @var Reply|MockObject $reply */
        $reply = $this->createMock(Reply::class);

        $reply
            ->method('getText')
            ->willReturn('FOO BAR')
        ;

        $this->extensions->enable($reply);

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
                $this->assertSame("VGhlVXNlcm5hbWUgNTU5YTE1OTc2YjFiZjk0ZmE2NmY4NGUzMWEzOWRmZDI=\r\n", $data);

                return new Success();
            })
        ;

        $this->smtpSocket
            ->expects($this->at(0))
            ->method('read')
            ->willReturn(new Success("334 PDQxOTI5NDIzNDEuMTI4Mjg0NzJAc291cmNlZm91ci5hbmRyZXcuY211LmVkdT4=\r\n"))
        ;

        $this->smtpSocket
            ->expects($this->at(1))
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }
}
