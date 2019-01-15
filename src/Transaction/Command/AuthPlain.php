<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Authentication;

class AuthPlain extends Command
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
