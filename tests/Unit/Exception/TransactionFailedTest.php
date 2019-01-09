<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\TransactionFailed;

class TransactionFailedTest extends TestCase
{
    public function testExceptionContainsCorrectMessageWithoutReason(): void
    {
        $this->expectExceptionMessage('Transaction failed.');

        throw new TransactionFailed();
    }

    public function testExceptionContainsCorrectMessageWithReason(): void
    {
        $this->expectExceptionMessage('Transaction failed with reason: `failure reason`.');

        throw new TransactionFailed('failure reason');
    }
}
