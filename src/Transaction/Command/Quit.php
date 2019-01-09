<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Command;

class Quit extends BaseCommand
{
    public function __construct()
    {
        parent::__construct('QUIT');
    }
}
