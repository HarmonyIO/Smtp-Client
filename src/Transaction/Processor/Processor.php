<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Connection\Buffer;

interface Processor
{
    public function process(Buffer $buffer): Promise;
}
