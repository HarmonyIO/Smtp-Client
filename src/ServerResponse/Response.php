<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse;

interface Response
{
    public static function isValid(string $line): bool;

    public function isLastResponse(): bool;
}
