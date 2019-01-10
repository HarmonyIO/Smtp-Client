<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Response;

class AuthCramMd5ResponseTest extends TestCase
{
    public function testUsernameIsAddedToResponse(): void
    {
        $command = new AuthCramMd5Response(new Authentication('TheUsername', 'ThePassword'), '12345');

        $this->assertStringStartsWith('TheUsername', base64_decode((string) $command));
    }

    public function testChallengeIsSignedWithPassword(): void
    {
        $command = new AuthCramMd5Response(new Authentication('TheUsername', 'ThePassword'), '12345');

        $this->assertStringEndsWith('2869585ba8dff0ae57ff1a3684a1646d', base64_decode((string) $command));
    }
}
