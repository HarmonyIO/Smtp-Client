<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\ClientAddress;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\FullyQualifiedDomainName;
use HarmonyIO\SmtpClient\Exception\InvalidFullyQualifiedDomainName;

class FullyQualifiedDomainNameTest extends TestCase
{
    public function testConstructorThrowsOnInvalidFullyQualifiedDomainName(): void
    {
        $this->expectException(InvalidFullyQualifiedDomainName::class);
        $this->expectExceptionMessage('Invalid fully qualified domain name (`foo`) supplied.');

        new FullyQualifiedDomainName('foo');
    }

    public function testGetAddress(): void
    {
        $this->assertSame('example.com', (new FullyQualifiedDomainName('example.com'))->getAddress());
    }
}
