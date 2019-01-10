<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;

class DataNotAcceptedTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('The data was not accepted by the server with the message: `300 I dun goofed`.');

        throw new DataNotAccepted('300 I dun goofed');
    }
}
