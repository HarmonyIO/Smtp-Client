<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Transaction\Command\RcptTo;

class RcptToTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("RCPT TO:<foo@example.com>\r\n", (string) (new RcptTo(new Address('foo@example.com'))));
    }
}
