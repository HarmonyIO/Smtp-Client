<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Extension;

use HarmonyIO\SmtpClient\Transaction\Reply\Reply;

final class Collection
{
    /** @var Factory */
    private $factory;

    /** @var object[] */
    private $enabledExtensions = [];

    public function __construct(Builder $factory)
    {
        $this->factory = $factory;
    }

    public function clearEnabledExtensions(): void
    {
        $this->enabledExtensions = [];
    }

    public function enable(Reply $reply): void
    {
        $extension = $this->factory->build($reply->getText());

        if ($extension === null) {
            return;
        }

        $this->enabledExtensions[get_class($extension)] = $extension;
    }

    public function isExtensionEnabled(string $fullyQualifiedExtensionName): bool
    {
        return isset($this->enabledExtensions[$fullyQualifiedExtensionName]);
    }

    public function getExtension(string $fullyQualifiedExtensionName): ?object
    {
        if (!$this->isExtensionEnabled($fullyQualifiedExtensionName)) {
            return null;
        }

        return $this->enabledExtensions[$fullyQualifiedExtensionName];
    }
}
