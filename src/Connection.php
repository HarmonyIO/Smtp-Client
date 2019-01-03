<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Socket\Socket;
use HarmonyIO\SmtpClient\Log\Output;
use function Amp\asyncCall;
use function Amp\Socket\connect;

class Connection
{
    /** @var ServerAddress */
    private $serverAddress;

    /** @var Authentication */
    private $authentication;

    /** @var Output */
    private $logger;

    public function __construct(ServerAddress $serverAddress, Authentication $authentication, Output $logger)
    {
        $this->serverAddress  = $serverAddress;
        $this->authentication = $authentication;
        $this->logger         = $logger;
    }

    public function connect(): void
    {
        asyncCall(function() {
            /** @var Socket $socket */
            $socket = yield connect(
                sprintf('tcp://%s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            $this->logger->info(
                sprintf('Opened connection to %s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            while (null !== $chunk = yield $socket->read()) {
                $this->logger->messageIn($chunk);
            }
        });
    }
}
