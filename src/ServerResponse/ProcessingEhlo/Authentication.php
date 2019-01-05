<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo;

use HarmonyIO\SmtpClient\ServerResponse\BaseResponse;

class Authentication extends BaseResponse
{
    private const PATTERN = '~^250[- ]AUTH (?P<authenticationMethods>.*)$~';

    private const SUPPORTED_AUTHENTICATION_METHODS = [
        'PLAIN',
        //'LOGIN',
        //'CRAM-MD5',
    ];

    /** @var string[] */
    private $authenticationMethods = [];

    public static function isValid(string $line): bool
    {
        return preg_match(self::PATTERN, $line) === 1;
    }

    public function __construct(string $line)
    {
        preg_match(self::PATTERN, $line, $matches);

        $this->authenticationMethods = array_filter(
            explode(' ', $matches['authenticationMethods']),
            [$this, 'filterSupportedAuthenticationMethods']
        );

        parent::__construct($line);
    }

    private function filterSupportedAuthenticationMethods(string $authenticationMethod): bool
    {
        return in_array(strtoupper($authenticationMethod), self::SUPPORTED_AUTHENTICATION_METHODS, true);
    }

    public function getPreferredAuthenticationMethod(): string
    {
        foreach (array_reverse(self::SUPPORTED_AUTHENTICATION_METHODS) as $supportedAuthenticationMethod) {
            if (!in_array($supportedAuthenticationMethod, $this->authenticationMethods, true)) {
                continue;
            }

            return $supportedAuthenticationMethod;
        }
    }
}
