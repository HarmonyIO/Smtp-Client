<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

use MyCLabs\Enum\Enum;

/**
 * @method static Level NONE()
 * @method static Level INFO()
 * @method static Level MESSAGE_IN()
 * @method static Level SMTP_IN()
 * @method static Level SMTP_OUT()
 * @method static Level DEBUG()
 * @method static Level ALL()
 */
class Level extends Enum
{
    public const NONE       = 0;
    public const INFO       = 1;
    public const MESSAGE_IN = 2;
    public const SMTP_IN    = 4;
    public const SMTP_OUT   = 8;
    public const DEBUG      = 16;
    public const ALL        = 31;
}
