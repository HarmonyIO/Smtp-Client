<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Command\Envelop;

use HarmonyIO\SmtpClient\Command\Command;
use HarmonyIO\SmtpClient\Envelop;

class MailFrom extends Command
{
    public function __construct(Envelop $envelop)
    {
        parent::__construct('MAIL', sprintf('FROM:%s', $envelop->getMailFromAddress()->getRfcAddress()));
    }
}
