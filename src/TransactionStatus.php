<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use MyCLabs\Enum\Enum;

/**
 * @method static TransactionStatus CONNECT()
 * @method static TransactionStatus SENT_EHLO()
 * @method static TransactionStatus PROCESSING_EHLO()
 * @method static TransactionStatus SENT_HELO()
 * @method static TransactionStatus STARTED_PLAIN_AUTH()
 * @method static TransactionStatus STARTED_LOGIN_AUTH()
 * @method static TransactionStatus STARTED_CRAM_MD5_AUTH()
 */
class TransactionStatus extends Enum
{
    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
    private const CONNECT               = 1;
    private const SENT_EHLO             = 2;
    private const PROCESSING_EHLO       = 3;
    private const SENT_HELO             = 4;
    private const STARTED_PLAIN_AUTH    = 5;
    private const STARTED_LOGIN_AUTH    = 6;
    private const STARTED_CRAM_MD5_AUTH = 7;
    // phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
}
