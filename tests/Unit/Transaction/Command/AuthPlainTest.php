<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Transaction\Command\AuthPlain;

class AuthPlainTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $command = new AuthPlain(new Authentication('TheUsername', 'ThePassword'));

        $this->assertSame('AUTH PLAIN ' . base64_encode("\0TheUsername\0ThePassword") . "\r\n", (string) $command);
    }
}
