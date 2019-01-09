<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ClientAddress;

/**
 * Note: this address is NOT in compliance with the RFC as it is not a publicly resolvable address
 *       yet, for local client of which we cannot know the public IP this is a decent work around
 */
class Localhost extends Ip
{
    public function __construct()
    {
        parent::__construct('127.0.0.1');
    }
}
