<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class ConnectionClosedUnexpectedly extends Smtp
{
    public function __construct()
    {
        parent::__construct('The connection closed while processing an SMTP reply.');
    }
}
