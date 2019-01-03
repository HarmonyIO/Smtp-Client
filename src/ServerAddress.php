<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Exception\InvalidPort;

class ServerAddress
{
    /** @var string */
    private $host;

    /** @var int */
    private $port;

    public function __construct(string $host, int $port = 587)
    {
        if ($port < 1) {
            throw new InvalidPort($port);
        }

        $this->host = $host;
        $this->port = $port;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getPort(): int
    {
        return $this->port;
    }
}
