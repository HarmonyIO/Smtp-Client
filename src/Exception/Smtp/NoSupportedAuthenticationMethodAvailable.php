<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class NoSupportedAuthenticationMethodAvailable extends Smtp
{
    /**
     * @param string[] $supportedAuthenticationMethods
     * @param string[] $availableAuthenticationMethods
     */
    public function __construct(array $supportedAuthenticationMethods, array $availableAuthenticationMethods)
    {
        parent::__construct(
            sprintf(
                'None of the available authentication methods (`%s`) is in the list of supported authentication methods (`%s`).',
                implode(' ', $availableAuthenticationMethods),
                implode(' ', $supportedAuthenticationMethods)
            )
        );
    }
}
