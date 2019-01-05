<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command\Auth;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Auth\StartLogIn;

class StartLoginTest extends TestCase
{
    public function testToStringFormatsCorrectly(): void
    {
        $command = (string) (new StartLogIn());

        $this->assertSame("AUTH LOGIN\r\n", $command);
    }
}
