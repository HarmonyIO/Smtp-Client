<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;

class PositiveIntermediateTest extends TestCase
{
    public function testIsValidReturnsFalseIfReplyDoesNotStartWith3(): void
    {
        $this->assertFalse(PositiveIntermediate::isValid('4'));
    }

    public function testIsValidReturnsTrueIfReplyStartsWith3(): void
    {
        $this->assertTrue(PositiveIntermediate::isValid('3'));
    }
}
