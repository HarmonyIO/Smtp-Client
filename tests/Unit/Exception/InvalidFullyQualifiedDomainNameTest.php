<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidFullyQualifiedDomainName;

class InvalidFullyQualifiedDomainNameTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Invalid fully qualified domain name (`foo`) supplied.');

        throw new InvalidFullyQualifiedDomainName('foo');
    }
}
