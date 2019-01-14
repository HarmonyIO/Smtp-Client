<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class ExtensionNegotiation extends Enum
{
    public const START_PROCESS             = 1;
    public const SENT_EHLO                 = 2;
    public const PROCESSING_EXTENSION_LIST = 3;
    public const SEND_HELO                 = 4;
    public const SENT_HELO                 = 5;
    public const COMPLETED                 = 6;
}
