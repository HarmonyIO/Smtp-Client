<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Envelop\Address;

final class RcptTo extends Command
{
    public function __construct(Address $address)
    {
        parent::__construct('RCPT', sprintf('TO:%s', $address->getRfcAddress()));
    }
}
