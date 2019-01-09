<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidEnumValue;

class InvalidEnumValueTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('`foo` is not a valid `DateTimeImmutable` value.');

        throw new InvalidEnumValue('foo', \DateTimeImmutable::class);
    }
}
