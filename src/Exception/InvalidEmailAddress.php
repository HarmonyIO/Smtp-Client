<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidEmailAddress extends Exception
{
    public function __construct(string $emailAddress)
    {
        parent::__construct(sprintf('The provided email address (`%s`) is invalid.', $emailAddress));
    }
}
