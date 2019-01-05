<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class UnsupportedExtension extends BaseResponse
{
    private const PATTERN = '/^250[\- ].*$/';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
