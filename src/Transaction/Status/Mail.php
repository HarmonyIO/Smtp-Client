<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class Mail extends Enum
{
    public const SENT_MAIL_FROM     = 1;
    public const SENDING_RECIPIENTS = 2;
    public const SENT_DATA          = 3;
    public const SENT_CONTENT       = 4;
    public const COMPLETED          = 5;
}
