<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class UnexpectedReply extends Smtp
{
    public function __construct(string $reply)
    {
        parent::__construct(sprintf('Encountered an unexpected server reply (`%s`).', $reply));
    }
}
