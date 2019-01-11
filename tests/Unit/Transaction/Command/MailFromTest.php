<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Transaction\Command\MailFrom;

class MailFromTest extends TestCase
{
    public function testToStringProperlyFormats(): void
    {
        $this->assertSame("MAIL FROM:<foo@example.com>\r\n", (string) (new MailFrom(new Address('foo@example.com'))));
    }
}
