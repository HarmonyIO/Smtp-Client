<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

use MyCLabs\Enum\Enum;

/**
 * @method static Level NONE()
 * @method static Level INFO()
 * @method static Level MESSAGE_IN()
 * @method static Level SMTP_IN()
 * @method static Level SMTP_OUT()
 * @method static Level SMTP()
 * @method static Level DEBUG()
 * @method static Level ALL()
 */
class Level extends Enum
{
    // phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
    private const NONE       = 0;
    private const INFO       = 1;
    private const MESSAGE_IN = 2;
    private const SMTP_IN    = 4;
    private const SMTP_OUT   = 8;
    private const SMTP       = 12;
    private const DEBUG      = 16;
    private const ALL        = 31;
    // phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedConstant
}
