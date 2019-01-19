<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class NoRecipientsAccepted extends Smtp
{
    public function __construct()
    {
        parent::__construct(sprintf('Non of the recipients were accepted by the server.'));
    }
}
