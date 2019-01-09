<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidEnumValue extends Exception
{
    /**
     * @param mixed $value
     */
    public function __construct($value, string $fullyQualifiedClassName)
    {
        parent::__construct(sprintf('`%s` is not a valid `%s` value.', $value, $fullyQualifiedClassName));
    }
}
