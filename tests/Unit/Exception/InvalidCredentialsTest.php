<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidCredentials;

class InvalidCredentialsTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Invalid SMTP credentials.');

        throw new InvalidCredentials();
    }
}
