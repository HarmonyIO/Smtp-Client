<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use MyCLabs\Enum\Enum;

/**
 * @method static TransactionStatus CONNECT()
 * @method static TransactionStatus SEND_EHLO()
 */
class TransactionStatus extends Enum
{
    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
    private const CONNECT   = 1;
    private const SEND_EHLO = 2;
    // phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
}
