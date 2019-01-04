<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use Amp\Socket\Socket as SocketConnection;
use HarmonyIO\SmtpClient\Log\Output;

/**
 * @method Promise end()
 */
class Socket
{
    /** @var Output */
    private $logger;

    /** @var SocketConnection */
    private $socket;

    public function __construct(Output $logger, SocketConnection $socket)
    {
        $this->logger = $logger;
        $this->socket = $socket;
    }

    public function write(string $data): Promise
    {
        $this->logger->smtpOut($data);

        return $this->socket->write($data);
    }

    /**
     * @return Promise<null|string>
     */
    public function read(): Promise
    {
        return $this->socket->read();
    }

    /**
     * @param mixed[] $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->socket->$name(...$arguments);
    }
}
