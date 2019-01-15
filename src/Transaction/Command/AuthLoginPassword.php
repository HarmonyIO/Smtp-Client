<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Authentication;

class AuthLoginPassword extends Command
{
    public function __construct(Authentication $authentication)
    {
        parent::__construct(base64_encode($authentication->getPassword()));
    }
}
