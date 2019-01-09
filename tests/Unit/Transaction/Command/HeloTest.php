<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\FullyQualifiedDomainName;
use HarmonyIO\SmtpClient\Transaction\Command\Helo;

class HeloTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("HELO example.com\r\n", (string) (new Helo(new FullyQualifiedDomainName('example.com'))));
    }
}
