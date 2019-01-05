<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Auth;

use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Command;
use HarmonyIO\SmtpClient\ServerResponse\StartedCramMd5Auth\Challenge;

class CramMd5Response extends Command
{
    public function __construct(Authentication $authentication, Challenge $challenge)
    {
        parent::__construct(base64_encode(
            sprintf('%s %s', $authentication->getUsername(), $this->generateResponse($authentication, $challenge))
        ));
    }

    private function generateResponse(Authentication $authentication, Challenge $challenge): string
    {
        return hash_hmac('md5', $challenge->getChallenge(), $authentication->getPassword());
    }
}
