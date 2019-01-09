<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ClientAddress;

use HarmonyIO\SmtpClient\Exception\InvalidFullyQualifiedDomainName;

class FullyQualifiedDomainName implements Address
{
    /** @var string */
    private $address;

    public function __construct(string $fullyQualifiedDomainName)
    {
        if (preg_match('~^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$~', $fullyQualifiedDomainName) !== 1) {
            throw new InvalidFullyQualifiedDomainName($fullyQualifiedDomainName);
        }

        $this->address = $fullyQualifiedDomainName;
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
