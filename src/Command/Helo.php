<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command;

class Helo extends Command
{
    public function __construct(string $identifier)
    {
        parent::__construct('HELO', $identifier);
    }
}
