<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Extension;

use HarmonyIO\SmtpClient\Exception\Smtp\NoSupportedAuthenticationMethodAvailable;

class Auth
{
    private const SUPPORTED_AUTHENTICATION_METHODS = [
        'PLAIN',
        'LOGIN',
        'CRAM-MD5',
    ];

    /** @var string[] */
    private $availableAuthenticationMethods = [];

    public function __construct(string $authenticationMethods)
    {
        $this->availableAuthenticationMethods = array_filter(
            explode(' ', $authenticationMethods),
            [$this, 'filterAvailableAuthenticationMethods']
        );
    }

    // phpcs:ignore SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
    private function filterAvailableAuthenticationMethods(string $authenticationMethod): bool
    {
        return in_array($authenticationMethod, self::SUPPORTED_AUTHENTICATION_METHODS, true);
    }

    public function getPreferredAuthenticationMethod(): string
    {
        foreach (array_reverse(self::SUPPORTED_AUTHENTICATION_METHODS) as $supportedAuthenticationMethod) {
            if (!in_array($supportedAuthenticationMethod, $this->availableAuthenticationMethods, true)) {
                continue;
            }

            return $supportedAuthenticationMethod;
        }

        throw new NoSupportedAuthenticationMethodAvailable(
            self::SUPPORTED_AUTHENTICATION_METHODS,
            $this->availableAuthenticationMethods
        );
    }
}
