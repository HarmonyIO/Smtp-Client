<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Extension;

final class Factory implements Builder
{
    public function build(string $replyText): ?object
    {
        if (preg_match('~^(?P<name>[^ ]+)(?: (?P<extraData>.+))?$~', $replyText, $matches) !== 1) {
            return null;
        }

        switch (strtoupper($matches['name'])) {
            case 'AUTH':
                return $this->buildAuthExtension($matches);

            case 'STARTTLS':
                return new StartTls();

            case 'PIPELINING':
                return new Pipelining();

            default:
                return null;
        }
    }

    /**
     * @param string[] $extensionInfo
     */
    private function buildAuthExtension(array $extensionInfo): Auth
    {
        return new Auth($extensionInfo['extraData']);
    }
}
