<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

use Monolog\Logger as MonoLogger;

final class Logger
{
    /** @var MonoLogger */
    private $incomingSmtpPacketsLogger;

    /** @var MonoLogger */
    private $outgoingSmtpPacketsLogger;

    /** @var MonoLogger */
    private $generalLogger;

    public function __construct(
        MonoLogger $incomingSmtpPacketsLogger,
        MonoLogger $outgoingSmtpPacketsLogger,
        MonoLogger $generalLogger
    ) {
        $this->incomingSmtpPacketsLogger = $incomingSmtpPacketsLogger;
        $this->outgoingSmtpPacketsLogger = $outgoingSmtpPacketsLogger;
        $this->generalLogger             = $generalLogger;
    }

    /**
     * @param mixed[] $context
     */
    public function smtpIn(string $message, array $context = []): void
    {
        $this->incomingSmtpPacketsLogger->info($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function smtpOut(string $message, array $context = []): void
    {
        $this->outgoingSmtpPacketsLogger->info($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->generalLogger->debug($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->generalLogger->info($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function notice(string $message, array $context = []): void
    {
        $this->generalLogger->notice($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function warning(string $message, array $context = []): void
    {
        $this->generalLogger->warning($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function error(string $message, array $context = []): void
    {
        $this->generalLogger->error($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function critical(string $message, array $context = []): void
    {
        $this->generalLogger->critical($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function alert(string $message, array $context = []): void
    {
        $this->generalLogger->alert($message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function emergency(string $message, array $context = []): void
    {
        $this->generalLogger->emergency($message, $context);
    }
}
