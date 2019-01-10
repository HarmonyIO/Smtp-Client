<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;

class InvalidMailFromAddressTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('The mail from address was not accepted by the server with the message: `300 I dun goofed`.');

        throw new InvalidMailFromAddress('300 I dun goofed');
    }
}
