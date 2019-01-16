<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\ConnectionClosedUnexpectedly;

class ConnectionClosedUnexpectedlyTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('The connection closed while processing an SMTP reply.');

        throw new ConnectionClosedUnexpectedly();
    }
}
