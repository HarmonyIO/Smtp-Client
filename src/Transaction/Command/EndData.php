<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class EndData extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('.');
    }
}
