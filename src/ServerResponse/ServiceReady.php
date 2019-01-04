<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse;

class ServiceReady implements Response
{
    private const PATTERN = '/^220 (?P<domain>[^ ]*)(:? (?P<serviceName>[^ ]*) ready)$/';

    /** @var string */
    private $domain;

    /** @var string */
    private $serviceName;

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        $this->domain      = $matches['domain'];
        $this->serviceName = $matches['serviceName'];
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function getServiceName(): string
    {
        return $this->serviceName;
    }
}
