<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Status;

use HarmonyIO\SmtpClient\Enum;

final class ExtensionNegotiation extends Enum
{
    public const SENT_EHLO                 = 1;
    public const PROCESSING_EXTENSION_LIST = 2;
    public const SENT_HELO                 = 3;
    public const COMPLETED                 = 4;
}
