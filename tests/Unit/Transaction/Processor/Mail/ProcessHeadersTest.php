<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor\Mail;

use Amp\Socket\ClientSocket;
use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop\Header;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessHeaders;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class ProcessHeadersTest extends TestCase
{
    /** @var Logger */
    private $logger;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var ClientSocket|MockObject $socket */
    private $socket;

    /** @var ProcessHeaders */
    private $processor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $this->smtpSocket = $this->createMock(SmtpSocket::class);
        $this->socket     = $this->createMock(ClientSocket::class);
        $this->processor  = new ProcessHeaders(
            new Socket($this->logger, $this->socket),
            new Header('Foo', 'Bar'),
            new Header('Baz', 'Qux')
        );
    }

    public function testProcessSendsAllHeaders(): void
    {
        $this->socket
            ->expects($this->at(0))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("Foo:Bar\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(1))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("Baz:Qux\r\n", $data);

                return new Success();
            })
        ;

        $this->socket
            ->expects($this->at(2))
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("\r\n", $data);

                return new Success();
            })
        ;

        /** @var Status $status */
        $status = wait($this->processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::SENT_HEADERS, $status->getValue());
    }

    public function testProcessSendsDelimiterEvenWhenThereAreNoHeaders(): void
    {
        $this->socket
            ->expects($this->once())
            ->method('write')
            ->willReturnCallback(function (string $data) {
                $this->assertSame("\r\n", $data);

                return new Success();
            })
        ;

        $processor  = new ProcessHeaders(new Socket($this->logger, $this->socket));

        /** @var Status $status */
        $status = wait($processor->process(new Buffer($this->smtpSocket, $this->logger)));

        $this->assertSame(Status::SENT_HEADERS, $status->getValue());
    }
}
