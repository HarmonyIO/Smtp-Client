<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class DeliveryStatusNotification extends BaseResponse
{
    private const PATTERN = '/^250[\- ]DSN$/';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }
}
