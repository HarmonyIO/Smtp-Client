<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\SmtpSocket;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class TransactionTest extends TestCase
{
    public function testRunRunsAllProcessors(): void
    {
        /** @var SmtpSocket|MockObject $socket */
        $socket = $this->createMock(SmtpSocket::class);

        /** @var Processor|MockObject $processor1 */
        $processor1 = $this->createMock(Processor::class);
        /** @var Processor|MockObject $processor2 */
        $processor2 = $this->createMock(Processor::class);

        $processor1
            ->expects($this->once())
            ->method('process')
            ->willReturn(new Success())
        ;

        $processor2
            ->expects($this->once())
            ->method('process')
            ->willReturn(new Success())
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        wait((new Transaction(new Buffer($socket, $logger)))->run($processor1, $processor2));
    }
}
