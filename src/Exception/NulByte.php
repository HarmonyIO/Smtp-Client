<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class NulByte extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid `NUL` byte encountered.');
    }
}
