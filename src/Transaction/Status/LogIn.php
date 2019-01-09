<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class LogIn extends Enum
{
    public const SENT_PLAIN                  = 1;
    public const SENT_LOGIN                  = 2;
    public const AWAITING_CRAM_MD5_CHALLENGE = 3;
    public const SENT_CRAM_MD5_RESPONSE      = 4;
    public const COMPLETED                   = 5;
}
