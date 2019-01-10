<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

final class Data extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('DATA');
    }
}
