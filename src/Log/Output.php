<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

class Output
{
    /** @var string[] */
    private $textualLevels = [];

    /** @var Level */
    private $logLevel;

    public function __construct(Level $level)
    {
        $this->textualLevels = [
            Level::INFO()->getValue()       => 'INFO',
            Level::MESSAGE_IN()->getValue() => 'INCOMING',
            Level::SMTP_IN()->getValue()    => 'SMTP_IN',
            Level::SMTP_OUT()->getValue()   => 'SMTP_OUT',
            Level::DEBUG()->getValue()      => 'DEBUG',
        ];

        $this->logLevel = $level;
    }

    /**
     * @param mixed[] $context
     */
    public function info(string $message, array $context = []): void
    {
        $this->log(Level::INFO(), $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function messageIn(string $message, array $context = []): void
    {
        $this->log(Level::MESSAGE_IN(), $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function smtpIn(string $message, array $context = []): void
    {
        $this->log(Level::SMTP_IN(), $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function smtpOut(string $message, array $context = []): void
    {
        $this->log(Level::SMTP_OUT(), $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log(Level::DEBUG(), $message, $context);
    }

    /**
     * @param mixed[] $context
     */
    public function log(Level $level, string $message, array $context = []): void
    {
        if (!$this->meetsLogLevel($level)) {
            return;
        }

        echo sprintf(
            '%s [%s] %s',
            (new \DateTime())->format('Y-m-d H:i:s'),
            $this->textualLevels[$level->getValue()],
            $this->replaceNonPrintableCharacters($message)
        ) . PHP_EOL;

        if (!$context) {
            return;
        }

        echo json_encode($context) . PHP_EOL;
    }

    private function meetsLogLevel(Level $level): bool
    {
        return (bool) ($this->logLevel->getValue() & $level->getValue());
    }

    private function replaceNonPrintableCharacters(string $message): string
    {
        return str_replace(["\r", "\n", "\t"], ['\r', '\n', '\t'], $message);
    }
}
