<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Fakes\Transaction;

use HarmonyIO\SmtpClient\Transaction\Reply\Reply;

class FakeValidReply extends Reply
{
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public static function isValid(string $line): bool
    {
        return true;
    }
}
