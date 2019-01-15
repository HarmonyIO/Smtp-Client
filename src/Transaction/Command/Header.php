<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Envelop\Header as EnvelopHeader;

final class Header extends Command
{
    public function __construct(EnvelopHeader $header)
    {
        parent::__construct(sprintf('%s:%s', $header->getKey(), $header->getValue()));
    }
}
