<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\NulByte;

class NulByteTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Invalid `NUL` byte encountered.');

        throw new NulByte();
    }
}
