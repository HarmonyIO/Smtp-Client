<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Envelop;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Envelop\Header;

class HeaderTest extends TestCase
{
    public function testGetKey(): void
    {
        $this->assertSame('TheKey', (new Header('TheKey', 'TheValue'))->getKey());
    }

    public function testGetValue(): void
    {
        $this->assertSame('TheValue', (new Header('TheKey', 'TheValue'))->getValue());
    }
}
