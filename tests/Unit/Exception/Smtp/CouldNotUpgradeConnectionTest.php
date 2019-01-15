<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\CouldNotUpgradeConnection;

class CouldNotUpgradeConnectionTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Could not upgrade connection. Server response: `300 I dun goofed`.');

        throw new CouldNotUpgradeConnection('300 I dun goofed');
    }
}
