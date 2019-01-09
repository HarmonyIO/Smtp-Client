<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class Handshake extends Enum
{
    public const AWAITING_GREETING = 1;
    public const AWAITING_BANNER   = 2;
    public const COMPLETED         = 3;
}
