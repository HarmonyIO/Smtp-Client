<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class ConnectionClosedUnexpectedly extends Exception
{
    public function __construct()
    {
        parent::__construct('The connection closed while processing an SMTP reply.');
    }
}
