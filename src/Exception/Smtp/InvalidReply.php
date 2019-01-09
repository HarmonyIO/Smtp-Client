<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class InvalidReply extends Smtp
{
    public function __construct(string $reply)
    {
        parent::__construct(sprintf('Invalid reply (`%s`) received from the server.', $reply));
    }
}
