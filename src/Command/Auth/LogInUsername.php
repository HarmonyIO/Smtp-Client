<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Auth;

use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Command;

class LogInUsername extends Command
{
    public function __construct(Authentication $authentication)
    {
        parent::__construct(base64_encode($authentication->getUsername()));
    }
}
