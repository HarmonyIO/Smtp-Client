<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Command\Ehlo;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerResponse\Factory as ServerResponseFactory;
use HarmonyIO\SmtpClient\ServerResponse\ServiceReady;

class Transaction
{
    /** @var Output */
    private $logger;

    /** @var Socket */
    private $socket;

    /** @var ServerResponseFactory */
    private $serverResponseFactory;

    /** @var TransactionStatus */
    private $status;

    public function __construct(Output $logger, Socket $socket, ServerResponseFactory $serverResponseFactory)
    {
        $this->logger                = $logger;
        $this->socket                = $socket;
        $this->serverResponseFactory = $serverResponseFactory;

        $this->status = TransactionStatus::CONNECT();
    }

    public function processLine(string $line): void
    {
        $this->logger->debug('Current client status is: ' . $this->status->getKey());

        $this->logger->smtpIn($line);

        try {
            $serverResponse = $this->serverResponseFactory->build($this->status, $line);
        } catch (\Throwable $e) {
            // @todo: should we quit here? Or throw? Or both?
            return;
        }

        $this->logger->debug('Server response object: ' . get_class($serverResponse));

        switch (get_class($serverResponse)) {
            case ServiceReady::class:
                $this->processServiceAvailability();
                return;
        }
    }

    private function processServiceAvailability(): void
    {
        $this->status = TransactionStatus::SEND_EHLO();

        $this->socket->write((string) new Ehlo('foo.bar'));
    }
}
