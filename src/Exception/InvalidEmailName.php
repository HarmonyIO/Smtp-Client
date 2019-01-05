<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class InvalidEmailName extends Exception
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('The provided name (`%s`) contains invalid characters.', $name));
    }
}
