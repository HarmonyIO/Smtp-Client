<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Helo;

class HeloTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("HELO example.com\r\n", (string) (new Helo('example.com')));
    }
}
