<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use Amp\Socket\Socket as SocketConnection;
use HarmonyIO\SmtpClient\Log\Output;

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
    public function write(string $data): Promise
    {
        $this->logger->smtpOut($data);

        return $this->socket->write($data);
    }

    /**
     * @return Promise<null>
     */
    public function end(string $data = ''): Promise
    {
        $this->logger->smtpOut($data);

        return $this->socket->end($data);
    }

    public function enableCrypto(?ClientTlsContext $tlsContext = null): Promise
    {
        return $this->socket->enableCrypto($tlsContext);
    }
}
