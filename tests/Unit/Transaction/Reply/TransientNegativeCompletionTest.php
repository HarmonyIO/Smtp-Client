<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;

class TransientNegativeCompletionTest extends TestCase
{
    public function testIsValidReturnsFalseIfReplyDoesNotStartWith4(): void
    {
        $this->assertFalse(TransientNegativeCompletion::isValid('5'));
    }

    public function testIsValidReturnsTrueIfReplyStartsWith4(): void
    {
        $this->assertTrue(TransientNegativeCompletion::isValid('4'));
    }
}
