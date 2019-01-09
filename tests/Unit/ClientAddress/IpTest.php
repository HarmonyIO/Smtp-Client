<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\ClientAddress;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\Ip;
use HarmonyIO\SmtpClient\Exception\InvalidIpAddress;

class IpTest extends TestCase
{
    public function testConstructorThrowsOnInvalidIp(): void
    {
        $this->expectException(InvalidIpAddress::class);
        $this->expectExceptionMessage('Invalid IP address (`foo`) supplied.');

        new Ip('foo');
    }

    public function testGetAddressReturnsFormattedIpv4Address(): void
    {
        $this->assertSame('[127.0.0.1]', (new Ip('127.0.0.1'))->getAddress());
    }

    public function testGetAddressReturnsFormattedIpv6Address(): void
    {
        $this->assertSame('[IPv6:::1]', (new Ip('::1'))->getAddress());
    }
}
