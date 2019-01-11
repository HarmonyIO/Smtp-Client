<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;

class PermanentNegativeCompletionTest extends TestCase
{
    public function testIsValidReturnsFalseIfReplyDoesNotStartWith5(): void
    {
        $this->assertFalse(PermanentNegativeCompletion::isValid('4'));
    }

    public function testIsValidReturnsTrueIfReplyStartsWith5(): void
    {
        $this->assertTrue(PermanentNegativeCompletion::isValid('5'));
    }
}
