<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Exception\InvalidEnumValue;

abstract class Enum
{
    /** @var mixed */
    private $value;

    /**
     * @param mixed $value
     */
    public function __construct($value)
    {
        $enumValues = (new \ReflectionClass($this))->getConstants();

        if (!array_search($value, $enumValues, true)) {
            throw new InvalidEnumValue($value, static::class);
        }

        $this->value = $value;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }
}
