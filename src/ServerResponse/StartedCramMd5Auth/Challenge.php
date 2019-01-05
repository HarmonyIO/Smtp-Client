<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\StartedCramMd5Auth;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class Challenge extends BaseResponse
{
    private const PATTERN = '~^334 (?P<challenge>[^ =]+=*)$~';

    /** @var string */
    private $challenge;

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        $this->challenge = base64_decode($matches['challenge']);

        parent::__construct($line);
    }

    public function getChallenge(): string
    {
        return $this->challenge;
    }
}
