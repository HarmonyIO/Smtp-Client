<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class Password extends BaseResponse
{
    private const PATTERN = '~^334 UGFzc3dvcmQ6~';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
