<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Connection;

use Amp\Promise;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\Socket as SocketConnection;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Command\Command;

final class Socket implements SmtpSocket
{
    /** @var Logger */
    private $logger;

    /** @var SocketConnection */
    private $socket;

    public function __construct(Logger $logger, ClientSocket $socket)
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
    public function write(Command $command): Promise
    {
        $this->logger->smtpOut((string) $command);

        return $this->socket->write((string) $command);
    }

    public function enableCrypto(?ClientTlsContext $tlsContext = null): Promise
    {
        return $this->socket->enableCrypto($tlsContext);
    }
}
