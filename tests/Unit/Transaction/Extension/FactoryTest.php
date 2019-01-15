<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Extension;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;
use HarmonyIO\SmtpClient\Transaction\Extension\Factory;
use HarmonyIO\SmtpClient\Transaction\Extension\StartTls;

class FactoryTest extends TestCase
{
    public function testBuildReturnsNullWhenReplyTextDoesNotContainAName(): void
    {
        $this->assertNull((new Factory())->build(''));
    }

    public function testBuildReturnsNullWhenExtensionIsNotSupported(): void
    {
        $this->assertNull((new Factory())->build('NOTSUPPORTED'));
    }

    public function testBuildBuildsTheAuthExtension(): void
    {
        $this->assertInstanceOf(Auth::class, (new Factory())->build('AUTH LOGIN'));
    }

    public function testBuildBuildsTheAuthExtensionWithExtraData(): void
    {
        /** @var Auth $auth */
        $auth = (new Factory())->build('AUTH LOGIN');

        $this->assertSame('LOGIN', $auth->getPreferredAuthenticationMethod());
    }

    public function testBuildBuildsTheStartTlsExtension(): void
    {
        $this->assertInstanceOf(StartTls::class, (new Factory())->build('STARTTLS'));
    }
}
