<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class StartTls extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('STARTTLS');
    }
}
