<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidEmailAddress;

class InvalidEmailAddressTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('The provided email address (`foo`) is invalid.');

        throw new InvalidEmailAddress('foo');
    }
}
