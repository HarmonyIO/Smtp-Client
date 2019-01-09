<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidIpAddress extends Exception
{
    public function __construct(string $ipAddress)
    {
        parent::__construct(sprintf('Invalid IP address (`%s`) supplied.', $ipAddress));
    }
}
