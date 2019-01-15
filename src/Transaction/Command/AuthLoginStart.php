<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class AuthLoginStart extends Command
{
    public function __construct()
    {
        parent::__construct('AUTH', 'LOGIN');
    }
}
