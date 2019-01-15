<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class CouldNotUpgradeConnection extends Smtp
{
    public function __construct(string $reply)
    {
        parent::__construct(sprintf('Could not upgrade connection. Server response: `%s`.', $reply));
    }
}
