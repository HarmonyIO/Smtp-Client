<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ClientAddress;

use HarmonyIO\SmtpClient\Exception\InvalidIpAddress;

class Ip implements Address
{
    /** @var string */
    private $address;

    public function __construct(string $ipAddress)
    {
        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            throw new InvalidIpAddress($ipAddress);
        }

        if (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->address = sprintf('[%s]', $ipAddress);
        } elseif (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->address = sprintf('[IPv6:%s]', $ipAddress);
        }
    }

    public function getAddress(): string
    {
        return $this->address;
    }
}
