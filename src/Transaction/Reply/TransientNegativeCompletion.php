<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

class TransientNegativeCompletion extends BaseReply
{
    public static function isValid(string $line): bool
    {
        return strpos($line, '4') === 0;
    }
}
