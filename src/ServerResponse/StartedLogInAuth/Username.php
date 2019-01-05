<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class Username extends BaseResponse
{
    private const PATTERN = '~^334 VXNlcm5hbWU6$~';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
