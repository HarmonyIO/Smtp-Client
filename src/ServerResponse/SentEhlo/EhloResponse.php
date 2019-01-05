<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\SentEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class EhloResponse extends BaseResponse
{
    private const PATTERN = '/^250(?:[- ](?P<domain>.*))?$/';

    /** @var string|null */
    private $domain;

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        if (isset($matches['domain'])) {
            $this->domain = $matches['domain'];
        }

        parent::__construct($line);
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
}
