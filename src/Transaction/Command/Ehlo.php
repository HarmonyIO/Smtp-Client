<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\ClientAddress\Address;

class Ehlo extends Command
{
    public function __construct(Address $clientAddress)
    {
        parent::__construct('EHLO', $clientAddress->getAddress());
    }
}
