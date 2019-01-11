<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;

class PositiveCompletionTest extends TestCase
{
    public function testIsValidReturnsFalseIfReplyDoesNotStartWith2(): void
    {
        $this->assertFalse(PositiveCompletion::isValid('3'));
    }

    public function testIsValidReturnsTrueIfReplyStartsWith2(): void
    {
        $this->assertTrue(PositiveCompletion::isValid('2'));
    }
}
