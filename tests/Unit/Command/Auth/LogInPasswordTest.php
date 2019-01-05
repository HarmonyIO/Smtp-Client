<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command\Auth;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Auth\LogInPassword;

class LogInPasswordTest extends TestCase
{
    public function testToStringFormatsCorrectly(): void
    {
        $command = (string) (new LogInPassword(new Authentication('foo', 'bar')));

        $this->assertSame(base64_encode('bar') . "\r\n", $command);
    }
}
