<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidPort;

class InvalidPortTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Invalid port supplied (`10`).');

        throw new InvalidPort(10);
    }
}
