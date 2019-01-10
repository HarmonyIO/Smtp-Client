<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class InvalidMailFromAddress extends Smtp
{
    public function __construct(string $line)
    {
        parent::__construct(sprintf('The mail from address was not accepted by the server with the message: `%s`.', $line));
    }
}
