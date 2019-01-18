<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Log;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Log\Logger;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger as MonoLogger;
use PHPUnit\Framework\MockObject\MockObject;

class LoggerTest extends TestCase
{
    public function testSmtpIn(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            $monoLogger,
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $logger->smtpIn('Test Message');
    }

    public function testSmtpOut(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger,
            new MonoLogger('GENERAL', [$this->createMock(AbstractProcessingHandler::class)])
        );

        $logger->smtpOut('Test Message');
    }

    public function testDebug(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('debug')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->debug('Test Message');
    }

    public function testInfo(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('info')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->info('Test Message');
    }

    public function testNotice(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('notice')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->notice('Test Message');
    }

    public function testWarning(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('warning')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->warning('Test Message');
    }

    public function testError(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('error')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->error('Test Message');
    }

    public function testCritical(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('critical')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->critical('Test Message');
    }

    public function testAlert(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('alert')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->alert('Test Message');
    }

    public function testEmergency(): void
    {
        /** @var MonoLogger|MockObject $monoLogger */
        $monoLogger = $this->createMock(MonoLogger::class);

        $monoLogger
            ->expects($this->once())
            ->method('emergency')
            ->willReturnCallback(function (string $message): void {
                $this->assertSame('Test Message', $message);
            })
        ;

        $logger = new Logger(
            new MonoLogger('SMTP_IN', [$this->createMock(AbstractProcessingHandler::class)]),
            new MonoLogger('SMTP_OUT', [$this->createMock(AbstractProcessingHandler::class)]),
            $monoLogger
        );

        $logger->emergency('Test Message');
    }
}
