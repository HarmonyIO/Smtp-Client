<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidCredentials extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid SMTP credentials.');
    }
}
