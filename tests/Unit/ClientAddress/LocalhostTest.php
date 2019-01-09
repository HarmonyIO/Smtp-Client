<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\ClientAddress;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;

class LocalhostTest extends TestCase
{
    public function testGetAddressReturnsFormattedAddress(): void
    {
        $this->assertSame('[127.0.0.1]', (new Localhost())->getAddress());
    }
}
