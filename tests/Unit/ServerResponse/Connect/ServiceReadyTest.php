<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\ServerResponse\Connect;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ServerResponse\Connect\ServiceReady;

class ServiceReadyTest extends TestCase
{
    public function testIsValidWithIncorrectCode(): void
    {
        $this->assertFalse(ServiceReady::isValid('221 foo.bar'));
    }
}
