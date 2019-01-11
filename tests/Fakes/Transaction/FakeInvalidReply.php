<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Fakes\Transaction;

use HarmonyIO\SmtpClient\Transaction\Reply\BaseReply;

class FakeInvalidReply extends BaseReply
{
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public static function isValid(string $line): bool
    {
        return false;
    }
}
