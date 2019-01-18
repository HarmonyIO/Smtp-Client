<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Connection;

use Amp\Loop;
use Amp\Socket\ServerSocket;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\PlainConnection;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\ServerAddress;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\asyncCall;
use function Amp\Socket\listen;

class PlainConnectionTest extends TestCase
{
    /** @var AbstractProcessingHandler|MockObject $logHandler */
    private $smtpInLogHandler;

    /** @var AbstractProcessingHandler|MockObject $logHandler */
    private $smtpOutLogHandler;

    /** @var AbstractProcessingHandler|MockObject $logHandler */
    private $generalLogHandler;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->smtpInLogHandler  = $this->createMock(AbstractProcessingHandler::class);
        $this->smtpOutLogHandler = $this->createMock(AbstractProcessingHandler::class);
        $this->generalLogHandler = $this->createMock(AbstractProcessingHandler::class);
    }

    public function testConnectLogs(): void
    {
        Loop::run(function (): \Generator {
            $this->generalLogHandler
                ->method('write')
                ->willReturnCallback(function (string $message): void {
                    $this->assertSame('Opened connection to 127.0.0.1:2525', $message);
                })
            ;

            $logger = new Logger(
                new MonoLogger('SMTP_IN', [$this->smtpInLogHandler]),
                new MonoLogger('SMTP_OUT', [$this->smtpOutLogHandler]),
                new MonoLogger('GENERAL', [$this->generalLogHandler])
            );

            $server = listen('127.0.0.1:2525');

            asyncCall(function () use ($server) {
                /** @var ServerSocket $socket */
                while ($socket = yield $server->accept()) {
                    asyncCall(function () use ($socket): void {
                        $this->assertInstanceOf(ServerSocket::class, $socket);
                    });
                }
            });

            yield (new PlainConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect();

            $server->close();

            Loop::stop();
        });
    }

    public function testConnectReturnsSmtpSocket(): void
    {
        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->smtpInLogHandler]),
            new MonoLogger('SMTP_OUT', [$this->smtpOutLogHandler]),
            new MonoLogger('GENERAL', [$this->generalLogHandler])
        );

        $server = listen('127.0.0.1:2525');

        $this->assertInstanceOf(
            SmtpSocket::class,
            (new PlainConnection(new ServerAddress('127.0.0.1', 2525), $logger))->connect()
        );

        $server->close();
    }
}
