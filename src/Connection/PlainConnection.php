<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Connection;

use Amp\Promise;
use Amp\Socket\ClientSocket;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\ServerAddress;
use function Amp\call;
use function Amp\Socket\connect;

final class PlainConnection implements Connection
{
    /** @var ServerAddress */
    private $serverAddress;

    /** @var Logger */
    private $logger;

    public function __construct(ServerAddress $serverAddress, Logger $logger)
    {
        $this->serverAddress  = $serverAddress;
        $this->logger         = $logger;
    }

    /**
     * @return Promise<Socket>
     */
    public function connect(): Promise
    {
        return call(function () {
            /** @var ClientSocket $socket */
            $socket = yield connect(
                sprintf('tcp://%s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            $this->logger->info(
                sprintf('Opened connection to %s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            return new Socket($this->logger, $socket);
        });
    }
}
