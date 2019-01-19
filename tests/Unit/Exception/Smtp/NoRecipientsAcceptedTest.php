<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\NoRecipientsAccepted;

class NoRecipientsAcceptedTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Non of the recipients were accepted by the server.');

        throw new NoRecipientsAccepted();
    }
}
