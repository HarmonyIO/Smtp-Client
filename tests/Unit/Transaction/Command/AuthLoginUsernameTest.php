<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginUsername;

class AuthLoginUsernameTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $command = new AuthLoginUsername(new Authentication('TheUsername', 'ThePassword'));

        $this->assertSame(base64_encode('TheUsername') . "\r\n", (string) $command);
    }
}
