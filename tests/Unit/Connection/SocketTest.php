<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class SocketTest extends TestCase
{
    /** @var ClientSocket|MockObject */
    private $socket;

    /** @var Logger */
    private $logger;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->socket = $this->createMock(ClientSocket::class);

        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );
    }

    public function testReadReturnsSocketData(): void
    {
        $this->socket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success('TheData'))
        ;

        $this->assertSame('TheData', (new Socket($this->logger, $this->socket))->read());
    }

    public function testWriteWritesToSocket(): void
    {
        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        wait((new Socket($this->logger, $this->socket))->write(new Quit()));
    }

    public function testEnableCrypto(): void
    {
        $this->socket
            ->expects($this->once())
            ->method('enableCrypto')
            ->willReturnCallback(function (ClientTlsContext $tlsContext) {
                $this->assertInstanceOf(ClientTlsContext::class, $tlsContext);

                return new Success();
            })
        ;

        wait((new Socket($this->logger, $this->socket))->enableCrypto(new ClientTlsContext()));
    }
}
