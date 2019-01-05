<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command\Auth;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Auth\Plain;

class PlainTest extends TestCase
{
    public function testToStringFormatsCorrectly(): void
    {
        $command = (string) (new Plain(new Authentication('foo', 'bar')));

        $this->assertSame('AUTH PLAIN ' . base64_encode("\0foo\0bar") . "\r\n", $command);
    }
}
