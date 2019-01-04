<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command;

class Ehlo extends Command
{
    public function __construct(string $identifier)
    {
        parent::__construct('EHLO', $identifier);
    }
}
