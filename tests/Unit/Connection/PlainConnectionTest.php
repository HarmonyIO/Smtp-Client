<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\PlainConnection;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;
use function Amp\Socket\listen;

class PlainConnectionTest extends TestCase
{
    public function testConnectLogs(): void
    {
        /** @var Output|MockObject $logger */
        $logger = $this->createMock(Output::class);

        $logger
            ->method('info')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Opened connection to 127.0.0.1:2525', $message);
            })
        ;

        $server = listen('127.0.0.1:2525');

        wait((new PlainConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect());

        $server->close();
    }

    public function testConnectReturnsSmtpSocket(): void
    {
        /** @var Output|MockObject $logger */
        $logger = $this->createMock(Output::class);

        $server = listen('127.0.0.1:2525');

        $this->assertInstanceOf(
            SmtpSocket::class,
            (new PlainConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect()
        );

        $server->close();
    }
}
