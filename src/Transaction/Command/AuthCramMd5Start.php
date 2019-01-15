<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class AuthCramMd5Start extends Command
{
    public function __construct()
    {
        parent::__construct('AUTH', 'CRAM-MD5');
    }
}
