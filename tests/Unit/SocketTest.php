<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use Amp\Socket\Socket as ServerSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class SocketTest extends TestCase
{
    /** @var ServerSocket|MockObject */
    private $socket;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->socket = $this->createMock(ServerSocket::class);
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
                $this->assertSame('TheData', $data);

                return new Success();
            })
        ;

        wait((new Socket($output, $this->socket))->write('TheData'));
    }

    public function testEndWritesToSocket(): void
    {
        $output = new Output(new Level(Level::NONE));

        $this->socket
            ->expects($this->once())
            ->method('end')
            ->willReturnCallback(function (string $data) {
                $this->assertSame('TheData', $data);

                return new Success();
            })
        ;

        wait((new Socket($output, $this->socket))->end('TheData'));
    }
}
