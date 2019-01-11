<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Extension;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\NoSupportedAuthenticationMethodAvailable;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;

class AuthTest extends TestCase
{
    public function testGetPreferredAuthenticationMethodFiltersOutUnsupportedAuthenticationMethods(): void
    {
        $auth = new Auth('NOT_SUPPORTED');

        $this->expectException(NoSupportedAuthenticationMethodAvailable::class);

        $auth->getPreferredAuthenticationMethod();
    }

    public function testGetPreferredAuthenticationMethodReturnsPlain(): void
    {
        $auth = new Auth('NOT_SUPPORTED_1 PLAIN NOT_SUPPORTED_2');

        $this->assertSame('PLAIN', $auth->getPreferredAuthenticationMethod());
    }

    public function testGetPreferredAuthenticationMethodReturnsLogin(): void
    {
        $auth = new Auth('NOT_SUPPORTED_1 PLAIN NOT_SUPPORTED_2 LOGIN');

        $this->assertSame('LOGIN', $auth->getPreferredAuthenticationMethod());
    }

    public function testGetPreferredAuthenticationMethodReturnsCramMd5(): void
    {
        $auth = new Auth('CRAM-MD5 NOT_SUPPORTED_1 PLAIN NOT_SUPPORTED_2 LOGIN');

        $this->assertSame('CRAM-MD5', $auth->getPreferredAuthenticationMethod());
    }
}
