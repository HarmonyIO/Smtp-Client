<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class SocketTest extends TestCase
{
    /** @var ClientSocket|MockObject */
    private $socket;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->socket = $this->createMock(ClientSocket::class);
    }

    public function testReadReturnsSocketData(): void
    {
        $output = new Output(new Level(Level::NONE));

        $this->socket
            ->expects($this->once())
            ->method('read')
            ->willReturn(new Success('TheData'))
        ;

        $this->assertSame('TheData', (new Socket($output, $this->socket))->read());
    }

    public function testWriteWritesToSocket(): void
    {
        $output = new Output(new Level(Level::NONE));

        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("QUIT\r\n", $data);

                return new Success();
            })
        ;

        wait((new Socket($output, $this->socket))->write(new Quit()));
    }

    public function testEnableCrypto(): void
    {
        $output = new Output(new Level(Level::NONE));

        $this->socket
            ->expects($this->once())
            ->method('enableCrypto')
            ->willReturnCallback(function (ClientTlsContext $tlsContext) {
                $this->assertInstanceOf(ClientTlsContext::class, $tlsContext);

                return new Success();
            })
        ;

        wait((new Socket($output, $this->socket))->enableCrypto(new ClientTlsContext()));
    }
}