<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class DataNotAccepted extends Smtp
{
    public function __construct(string $line)
    {
        parent::__construct(sprintf('The data was not accepted by the server with the message: `%s`.', $line));
    }
}
