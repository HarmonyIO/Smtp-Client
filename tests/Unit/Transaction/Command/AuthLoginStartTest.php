<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginStart;

class AuthLoginStartTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("AUTH LOGIN\r\n", (string) (new AuthLoginStart()));
    }
}
