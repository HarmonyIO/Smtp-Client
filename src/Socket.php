<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\Socket as SocketConnection;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Command\BaseCommand;

final class Socket implements SmtpSocket
{
    /** @var Output */
    private $logger;

    /** @var SocketConnection */
    private $socket;

    public function __construct(Output $logger, ClientSocket $socket)
    {
        $this->logger = $logger;
        $this->socket = $socket;
    }

    /**
     * @return Promise<null|string>
     */
    public function read(): Promise
    {
        return $this->socket->read();
    }

    /**
     * @return Promise<null>
     */
    public function write(BaseCommand $command): Promise
    {
        $this->logger->smtpOut((string) $command);

        return $this->socket->write((string) $command);
    }

    public function enableCrypto(?ClientTlsContext $tlsContext = null): Promise
    {
        return $this->socket->enableCrypto($tlsContext);
    }
}
