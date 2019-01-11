<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Exception\NulByte;
use HarmonyIO\SmtpClient\Exception\SpaceCharacter;

class Authentication
{
    /** @var string */
    private $username;

    /** @var string */
    private $password;

    public function __construct(string $username, string $password)
    {
        if (strpos($username, "\0") !== false) {
            throw new NulByte();
        }

        if (strpos($username, ' ') !== false) {
            throw new SpaceCharacter();
        }

        if (strpos($password, "\0") !== false) {
            throw new NulByte();
        }

        $this->username = $username;
        $this->password = $password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getPassword(): string
    {
        return $this->password;
    }
}
