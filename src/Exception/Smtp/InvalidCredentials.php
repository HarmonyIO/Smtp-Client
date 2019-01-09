<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class InvalidCredentials extends Smtp
{
    public function __construct()
    {
        parent::__construct('Invalid SMTP credentials.');
    }
}
