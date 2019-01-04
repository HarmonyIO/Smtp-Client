<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\SentEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class EhloResponse extends BaseResponse
{
    private const PATTERN = '/^250/';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
