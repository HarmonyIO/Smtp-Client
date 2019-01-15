<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Connection;

use Amp\Promise;
use Amp\Socket\ClientSocket;
use Amp\Socket\ClientTlsContext;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;
use HarmonyIO\SmtpClient\Socket;
use function Amp\call;
use function Amp\Socket\cryptoConnect;

final class TlsConnection implements Connection
{
    /** @var ServerAddress */
    private $serverAddress;

    /** @var Output */
    private $logger;

    public function __construct(ServerAddress $serverAddress, Output $logger)
    {
        $this->serverAddress  = $serverAddress;
        $this->logger         = $logger;
    }

    /**
     * @return Promise<Socket>
     */
    public function connect(?ClientTlsContext $tlsContext = null): Promise
    {
        return call(function () use ($tlsContext) {
            /** @var ClientSocket $socket */
            $socket = yield cryptoConnect(
                sprintf('tcp://%s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort()),
                null,
                $tlsContext
            );

            $this->logger->info(
                sprintf('Opened connection to %s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            return new Socket($this->logger, $socket);
        });
    }
}
