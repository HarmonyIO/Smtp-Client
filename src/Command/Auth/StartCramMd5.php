<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Auth;

use HarmonyIO\SmtpClient\Command\Command;

class StartCramMd5 extends Command
{
    public function __construct()
    {
        parent::__construct('AUTH', 'CRAM-MD5');
    }
}
