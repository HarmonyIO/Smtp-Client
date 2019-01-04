<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Ehlo;

class EhloTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("EHLO example.com\r\n", (string) (new Ehlo('example.com')));
    }
}
