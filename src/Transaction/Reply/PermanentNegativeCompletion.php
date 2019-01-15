<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

class PermanentNegativeCompletion extends Reply
{
    public static function isValid(string $line): bool
    {
        return strpos($line, '5') === 0;
    }
}
