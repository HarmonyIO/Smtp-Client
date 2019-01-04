<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\Connect;

use HarmonyIO\SmtpClient\ServerResponse\Response;

class InvalidCommand implements Response
{
    private const PATTERN = '/^500/';

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);
    }
}
