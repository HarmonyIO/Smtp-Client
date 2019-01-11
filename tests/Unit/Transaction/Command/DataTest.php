<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\Data;

class DataTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("DATA\r\n", (string) (new Data()));
    }
}
