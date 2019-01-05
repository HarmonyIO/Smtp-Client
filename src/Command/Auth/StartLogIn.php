<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Auth;

use HarmonyIO\SmtpClient\Command\Command;

class StartLogIn extends Command
{
    public function __construct()
    {
        parent::__construct('AUTH', 'LOGIN');
    }
}
