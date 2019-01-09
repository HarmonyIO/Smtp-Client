<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\NoSupportedAuthenticationMethodAvailable;

class NoSupportedAuthenticationMethodAvailableTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('None of the available authentication methods (`available`) is in the list of supported authentication methods (`first second`).');

        throw new NoSupportedAuthenticationMethodAvailable(['first', 'second'], ['available']);
    }
}
