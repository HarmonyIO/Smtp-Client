<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Envelop;

use HarmonyIO\SmtpClient\Exception\InvalidEmailAddress;
use HarmonyIO\SmtpClient\Exception\InvalidEmailName;

class Address
{
    /** @var string */
    private $emailAddress;

    /** @var string|null */
    private $name;

    public function __construct(string $emailAddress, ?string $name = null)
    {
        if (preg_match('~^[^<>\r\n]+@[^<>\r\n]+\.[^<>\r\n]+$~D', $emailAddress) !== 1) {
            throw new InvalidEmailAddress($emailAddress);
        }

        if ($name !== null && (strpos($name, "\r") !== false || strpos($name, "\n") !== false)) {
            throw new InvalidEmailName($name);
        }

        $this->emailAddress = $emailAddress;
        $this->name         = $name;
    }

    public function getEmailAddress(): string
    {
        return $this->emailAddress;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getRfcAddress(): string
    {
        if ($this->name === null) {
            return sprintf('<%s>', $this->emailAddress);
        }

        return sprintf('<%s> %s', $this->emailAddress, $this->name);
    }
}
