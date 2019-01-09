<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\FullyQualifiedDomainName;
use HarmonyIO\SmtpClient\Transaction\Command\Ehlo;

class EhloTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("EHLO example.com\r\n", (string) (new Ehlo(new FullyQualifiedDomainName('example.com'))));
    }
}
