<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use Amp\Loop;
use Amp\Socket\Certificate;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\ServerSocket;
use Amp\Socket\ServerTlsContext;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\TlsConnection;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\asyncCall;
use function Amp\Socket\listen;

class TlsConnectionTest extends TestCase
{
    public function testConnectLogs(): void
    {
        Loop::run(function (): \Generator {
            /** @var Output|MockObject $logger */
            $logger = $this->createMock(Output::class);

            $logger
                ->method('info')
                ->willReturnCallback(function (string $message): void {
                    $this->assertSame('Opened connection to 127.0.0.1:2525', $message);
                })
            ;

            $tlsContext = (new ServerTlsContext())
                ->withDefaultCertificate(new Certificate(TEST_DATA_DIR . '/harmony.io.pem'))
            ;

            $server = listen('127.0.0.1:2525', null, $tlsContext);

            asyncCall(function () use ($server) {
                /** @var ServerSocket $socket */
                while ($socket = yield $server->accept()) {
                    asyncCall(function () use ($socket) {
                        yield $socket->enableCrypto();

                        $this->assertInstanceOf(ServerSocket::class, $socket);
                    });
                }
            });

            $context = (new ClientTlsContext())
                ->withPeerName('harmony.io')
                ->withCaFile(TEST_DATA_DIR . '/harmony.io.crt')
            ;

            yield (new TlsConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect($context);

            $server->close();

            Loop::stop();
        });
    }

    public function testConnectReturnsSmtpSocket(): void
    {
        Loop::run(function (): void {
            /** @var Output|MockObject $logger */
            $logger = $this->createMock(Output::class);

            $tlsContext = (new ServerTlsContext())
                ->withDefaultCertificate(new Certificate(TEST_DATA_DIR . '/harmony.io.pem'))
            ;

            $server = listen('127.0.0.1:2525', null, $tlsContext);

            asyncCall(function () use ($server) {
                /** @var ServerSocket $socket */
                while ($socket = yield $server->accept()) {
                    asyncCall(function () use ($socket) {
                        yield $socket->enableCrypto();

                        $this->assertInstanceOf(ServerSocket::class, $socket);
                    });
                }
            });

            $context = (new ClientTlsContext())
                ->withPeerName('harmony.io')
                ->withCaFile(TEST_DATA_DIR . '/harmony.io.crt')
            ;

            $this->assertInstanceOf(
                SmtpSocket::class,
                (new TlsConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect($context)
            );

            $server->close();

            Loop::stop();
        });
    }
}
