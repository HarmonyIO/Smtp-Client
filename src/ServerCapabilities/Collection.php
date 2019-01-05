<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerCapabilities;

class Collection
{
    /** @var object[] */
    private $capabilities = [];

    public function addCapability(object $capability): void
    {
        $this->capabilities[get_class($capability)] = $capability;
    }

    public function isCapableOf(string $fullyQualifiedCapabilityName): bool
    {
        return isset($this->capabilities[$fullyQualifiedCapabilityName]);
    }

    public function getCapability(string $fullyQualifiedCapabilityName): ?object
    {
        if (!$this->isCapableOf($fullyQualifiedCapabilityName)) {
            return null;
        }

        return $this->capabilities[$fullyQualifiedCapabilityName];
    }
}
