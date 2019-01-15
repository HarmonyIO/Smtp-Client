<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\StartTls;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;

class ExtensionNegotiationTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var Builder|MockObject $socket */
    private $extensionFactory;

    /** @var ExtensionNegotiation */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->socket           = $this->createMock(ClientSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->processor        = new ExtensionNegotiation(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            new Localhost(),
            new Collection($this->extensionFactory)
        );
    }

    public function testProcessProcessesEntireExtensionNegotiationWhenEhloIsNotSupported(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("500 error\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $this->assertNull($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessProcessesEntireExtensionNegotiationWhenNoExtensionsAreAvailable(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturn(new Success("200 success\r\n"))
        ;

        $this->assertNull($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessProcessesEntireExtensionNegotiationWhenExtensionsAreAvailable(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200-success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $this->assertNull($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }

    public function testProcessProcessesEntireExtensionNegotiationWhenStartTlsIsAvailable(): void
    {
        $this->smtpSocket
            ->method('read')
            ->willReturnOnConsecutiveCalls(
                new Success("200-success\r\n"),
                new Success("200 STARTTLS\r\n"),
                new Success("200 success\r\n"),
                new Success("200 success\r\n")
            )
        ;

        $this->extensionFactory
            ->method('build')
            ->willReturn(new StartTls())
        ;

        $this->socket
            ->method('write')
            ->willReturn(new Success())
        ;

        $this->socket
            ->method('enableCrypto')
            ->willReturn(new Success())
        ;

        $this->assertNull($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));
    }
}
