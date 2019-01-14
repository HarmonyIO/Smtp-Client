<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class Mail extends Enum
{
    public const SENT_MAIL_FROM     = 1;
    public const SENDING_RECIPIENTS = 2;
    public const SENT_RECIPIENTS    = 3;
    public const SENT_DATA          = 4;
    public const SENDING_HEADERS    = 5;
    public const SENT_HEADERS       = 6;
    public const SENT_CONTENT       = 7;
    public const COMPLETED          = 8;
}
