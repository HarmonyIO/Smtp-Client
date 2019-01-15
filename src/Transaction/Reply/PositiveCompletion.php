<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

class PositiveCompletion extends Reply
{
    public static function isValid(string $line): bool
    {
        return strpos($line, '2') === 0;
    }
}
