<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Quit;

class QuitTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("QUIT\r\n", (string) (new Quit()));
    }
}
