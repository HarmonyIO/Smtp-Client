<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Auth;

use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Command;

class Plain extends Command
{
    public function __construct(Authentication $authentication)
    {
        parent::__construct(
            'AUTH',
            'PLAIN',
            base64_encode(sprintf("\0%s\0%s", $authentication->getUsername(), $authentication->getPassword()))
        );
    }
}
