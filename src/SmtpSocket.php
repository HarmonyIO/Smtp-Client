<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use Amp\Socket\ClientTlsContext;
use HarmonyIO\SmtpClient\Transaction\Command\BaseCommand;

interface SmtpSocket
{
    /**
     * @return Promise<null|string>
     */
    public function read(): Promise;

    /**
     * @return Promise<null>
     */
    public function write(BaseCommand $command): Promise;

    public function enableCrypto(?ClientTlsContext $tlsContext = null): Promise;
}
