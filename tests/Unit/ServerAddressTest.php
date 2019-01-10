<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidPort;
use HarmonyIO\SmtpClient\ServerAddress;

class ServerAddressTest extends TestCase
{
    public function testConstructorThrowsOnInvalidPort(): void
    {
        $this->expectException(InvalidPort::class);
        $this->expectExceptionMessage('Invalid port supplied (`-3`).');

        new ServerAddress('example.com', -3);
    }

    public function testGetHost(): void
    {
        $this->assertSame('example.com', (new ServerAddress('example.com', 25))->getHost());
    }

    public function testGetPort(): void
    {
        $this->assertSame(25, (new ServerAddress('example.com', 25))->getPort());
    }
}
