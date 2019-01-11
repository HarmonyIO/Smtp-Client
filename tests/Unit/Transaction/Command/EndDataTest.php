<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\EndData;

class EndDataTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame(".\r\n", (string) (new EndData()));
    }
}
