<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Start;

class AuthCramMd5StartTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("AUTH CRAM-MD5\r\n", (string) (new AuthCramMd5Start()));
    }
}
