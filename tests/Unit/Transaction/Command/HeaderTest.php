<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Envelop\Header as EnvelopHeader;
use HarmonyIO\SmtpClient\Transaction\Command\Header;

class HeaderTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("TheKey:TheValue\r\n", (string) (new Header(new EnvelopHeader('TheKey', 'TheValue'))));
    }
}
