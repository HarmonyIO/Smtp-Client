<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use Amp\Socket\Socket as AmpSocket;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerResponse\Factory as ServerResponseFactory;
use function Amp\call;
use function Amp\Socket\connect;

class Connection
{
    private const MAX_CUNK_SIZE = 512;

    private const LINE_DELIMITER = "\r\n";

    /** @var ServerAddress */
    private $serverAddress;

    /** @var Output */
    private $logger;

    public function __construct(ServerAddress $serverAddress, Output $logger)
    {
        $this->serverAddress = $serverAddress;
        $this->logger        = $logger;
    }

    public function connect(): Promise
    {
        return call(function () {
            /** @var AmpSocket $socket */
            $socket = yield connect(
                sprintf('tcp://%s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            $this->logger->info(
                sprintf('Opened connection to %s:%s', $this->serverAddress->getHost(), $this->serverAddress->getPort())
            );

            $socket = new Socket($this->logger, $socket);
            $transaction = new Transaction($this->logger, $socket, new ServerResponseFactory());

            $buffer = '';
            // phpcs:ignore SlevomatCodingStandard.ControlStructures.DisallowYodaComparison.DisallowedYodaComparison
            while (null !== $chunk = yield $socket->read()) {
                $this->logger->messageIn($chunk);

                $buffer .= $chunk;

                // phpcs:ignore SlevomatCodingStandard.ControlStructures.DisallowYodaComparison.DisallowedYodaComparison
                while (false !== $pos = strpos($buffer, self::LINE_DELIMITER)) {
                    $line   = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + strlen(self::LINE_DELIMITER));

                    if (strlen($line) > self::MAX_CUNK_SIZE) {
                        //$socket->write((string) new SyntaxError('Line length limit exceeded.'));
                        //continue;
                    }

                    $transaction->processLine($line);
                }
            }
        });
    }
}
