<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Fakes\Transaction;

use HarmonyIO\SmtpClient\Transaction\Reply\BaseReply;

class FakeReply extends BaseReply
{
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public static function isValid(string $line): bool
    {
        return true;
    }
}
