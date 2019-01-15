<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\StartTls;

class StartTlsTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("STARTTLS\r\n", (string) (new StartTls()));
    }
}
