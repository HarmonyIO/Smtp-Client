<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Connection;

use Amp\Promise;
use Amp\Socket\ClientTlsContext;
use HarmonyIO\SmtpClient\Transaction\Command\Command;

interface SmtpSocket
{
    /**
     * @return Promise<null|string>
     */
    public function read(): Promise;

    /**
     * @return Promise<null>
     */
    public function write(Command $command): Promise;

    public function enableCrypto(?ClientTlsContext $tlsContext = null): Promise;
}
