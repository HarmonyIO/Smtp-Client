<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Envelop\Address;

final class MailFrom extends Command
{
    public function __construct(Address $address)
    {
        parent::__construct('MAIL', sprintf('FROM:%s', $address->getRfcAddress()));
    }
}
