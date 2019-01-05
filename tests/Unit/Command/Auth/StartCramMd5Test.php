<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command\Auth;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Auth\StartCramMd5;

class StartCramMd5Test extends TestCase
{
    public function testToStringFormatsCorrectly(): void
    {
        $command = (string) (new StartCramMd5());

        $this->assertSame("AUTH CRAM-MD5\r\n", $command);
    }
}
