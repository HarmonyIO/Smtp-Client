<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class MessageSizeDeclaration extends BaseResponse
{
    private const PATTERN = '~^250[- ]SIZE (?P<size>\d+)$~';

    /** @var int */
    private $sizeInBytes;

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        $this->sizeInBytes = (int) $matches['size'];

        parent::__construct($line);
    }

    public function getSize(): int
    {
        return $this->sizeInBytes;
    }
}
