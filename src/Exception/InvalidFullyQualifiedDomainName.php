<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidFullyQualifiedDomainName extends Exception
{
    public function __construct(string $fullyQualifiedDomainName)
    {
        parent::__construct(sprintf('Invalid fully qualified domain name (`%s`) supplied.', $fullyQualifiedDomainName));
    }
}
