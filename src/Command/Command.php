<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command;

abstract class Command
{
    /** @var string */
    private $command;

    /** @var string[] */
    private $extraData;

    public function __construct(string $command, string ...$extraData)
    {
        $this->command   = $command;
        $this->extraData = $extraData;
    }

    public function __toString(): string
    {
        if (!$this->extraData) {
            return sprintf("%s\r\n", $this->command);
        }

        return sprintf("%s %s\r\n", $this->command, implode(' ', $this->extraData));
    }
}
