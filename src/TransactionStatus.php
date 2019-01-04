<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use MyCLabs\Enum\Enum;

/**
 * @method static TransactionStatus CONNECT()
 * @method static TransactionStatus SENT_EHLO()
 * @method static TransactionStatus SENT_HELO()
 */
class TransactionStatus extends Enum
{
    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
    private const CONNECT   = 1;
    private const SENT_EHLO = 2;
    private const SENT_HELO = 3;
    // phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
}
