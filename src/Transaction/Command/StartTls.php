<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class StartTls extends Command
{
    public function __construct()
    {
        parent::__construct('STARTTLS');
    }
}
