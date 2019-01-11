<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use Amp\Success;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Transaction;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
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

        wait((new Transaction(new Buffer($socket, new Output(new Level(Level::NONE)))))->run($processor1, $processor2));
    }
}
