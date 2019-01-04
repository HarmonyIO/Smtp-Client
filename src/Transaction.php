<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Command\Ehlo;
use HarmonyIO\SmtpClient\Command\Helo;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerCapabilities\Collection;
use HarmonyIO\SmtpClient\ServerResponse\Connect\ServiceReady;
use HarmonyIO\SmtpClient\ServerResponse\Factory as ServerResponseFactory;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\DeliveryStatusNotification;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\EhloResponse;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\InvalidCommand;

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

    /** @var Collection */
    private $smtpCapabilities;

    public function __construct(Output $logger, Socket $socket, ServerResponseFactory $serverResponseFactory)
    {
        $this->logger                = $logger;
        $this->socket                = $socket;
        $this->serverResponseFactory = $serverResponseFactory;

        $this->status           = TransactionStatus::CONNECT();
        $this->smtpCapabilities = new Collection();
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

            case EhloResponse::class:
                $this->processEhloSupported();
                return;

            case InvalidCommand::class:
                $this->processEhloNotSupported();
                return;

            case DeliveryStatusNotification::class:
                /** @var DeliveryStatusNotification $serverResponse */
                $this->processDsnCapability($serverResponse);
                return;
        }
    }

    private function processServiceAvailability(): void
    {
        $this->status = TransactionStatus::SENT_EHLO();

        $this->socket->write((string) new Ehlo('foo.bar'));
    }

    private function processEhloSupported(): void
    {
        $this->status = TransactionStatus::PROCESSING_EHLO();
    }

    private function processEhloNotSupported(): void
    {
        $this->status = TransactionStatus::SENT_HELO();

        $this->socket->write((string) new Helo('foo.bar'));
    }

    private function processDsnCapability(DeliveryStatusNotification $dsnCapability): void
    {
        $this->smtpCapabilities->addCapability($dsnCapability);

        if (!$dsnCapability->isLastResponse()) {
            return;
        }
    }
}
