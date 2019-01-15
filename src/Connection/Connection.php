<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Connection;

use Amp\Promise;
use HarmonyIO\SmtpClient\Socket;

interface Connection
{
    /**
     * @return Promise<Socket>
     */
    public function connect(): Promise;
}
