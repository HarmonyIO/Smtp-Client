<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class ExtensionNegotiation extends Enum
{
    public const START_PROCESS             = 1;
    public const START_TLS_PROCESS         = 2;
    public const SENT_EHLO                 = 3;
    public const PROCESSING_EXTENSION_LIST = 4;
    public const SEND_HELO                 = 5;
    public const PROCESS_STARTTLS          = 6;
    public const STARTING_TLS              = 7;
    public const SENT_HELO                 = 8;
    public const COMPLETED                 = 9;
}
