<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

use HarmonyIO\SmtpClient\Enum;

class Level extends Enum
{
    public const NONE       = 0;
    public const INFO       = 1;
    public const MESSAGE_IN = 2;
    public const SMTP_IN    = 4;
    public const SMTP_OUT   = 8;
    public const SMTP       = 12;
    public const DEBUG      = 16;
    public const ALL        = 31;
}
