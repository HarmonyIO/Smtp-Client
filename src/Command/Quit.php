<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command;

class Quit extends Command
{
    public function __construct()
    {
        parent::__construct('QUIT');
    }
}
