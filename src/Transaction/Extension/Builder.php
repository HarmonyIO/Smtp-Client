<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Extension;

interface Builder
{
    public function build(string $replyText): ?object;
}
