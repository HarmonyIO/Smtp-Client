<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ClientAddress;

interface Address
{
    public function getAddress(): string;
}
