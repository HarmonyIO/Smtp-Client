<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class BodyLine extends Command
{
    public function __construct(string $line)
    {
        if ($line === '.') {
            $line = '..';
        }

        parent::__construct($line);
    }
}
