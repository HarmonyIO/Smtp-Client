<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;

class QuitTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("QUIT\r\n", (string) (new Quit()));
    }
}
