<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

use HarmonyIO\SmtpClient\Authentication;

class AuthCramMd5Response extends Command
{
    public function __construct(Authentication $authentication, string $challenge)
    {
        parent::__construct(base64_encode(
            sprintf('%s %s', $authentication->getUsername(), $this->generateResponse($authentication, $challenge))
        ));
    }

    private function generateResponse(Authentication $authentication, string $challenge): string
    {
        return hash_hmac('md5', base64_decode($challenge), $authentication->getPassword());
    }
}
