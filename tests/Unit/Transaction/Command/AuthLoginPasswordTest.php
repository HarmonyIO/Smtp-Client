<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginPassword;

class AuthLoginPasswordTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $command = new AuthLoginPassword(new Authentication('TheUsername', 'ThePassword'));

        $this->assertSame(base64_encode('ThePassword') . "\r\n", (string) $command);
    }
}
