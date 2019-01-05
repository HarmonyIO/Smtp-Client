<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\SentMailFrom;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class AcceptedMailFrom extends BaseResponse
{
    private const PATTERN = '~^250(:? (?P<extendedStatusCode>\d\.\d\.\d))?(:? (?P<textualStatusCode>.*))?$~';

    /** @var string|null */
    private $extendedStatusCode = null;

    /** @var string|null */
    private $textualStatusCode = null;

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        if (isset($matches['extendedStatusCode'])) {
            $this->extendedStatusCode = $matches['extendedStatusCode'];
        }

        if (isset($matches['textualStatusCode'])) {
            $this->textualStatusCode = $matches['textualStatusCode'];
        }

        parent::__construct($line);
    }

    public function getExtendedStatusCode(): ?string
    {
        return $this->extendedStatusCode;
    }

    public function getTextualStatusCode(): ?string
    {
        return $this->textualStatusCode;
    }
}
