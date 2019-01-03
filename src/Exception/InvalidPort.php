<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidPort extends Exception
{
    public function __construct(int $port)
    {
        parent::__construct(sprintf('Invalid port supplied (`%s`).', $port));
    }
}
