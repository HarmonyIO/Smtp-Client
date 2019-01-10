<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\BodyLine;

class BodyLineTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $command = new BodyLine('The line.');

        $this->assertSame("The line.\r\n", (string) $command);
    }

    public function testToStringProperlyFormatsLineWithJustADot(): void
    {
        $command = new BodyLine('.');

        $this->assertSame("..\r\n", (string) $command);
    }
}
