<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\ClientAddress\Address;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Command\Ehlo;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

class ProcessEhlo implements Processor
{
    private const ALLOWED_REPLIES = [
        PositiveCompletion::class,
        TransientNegativeCompletion::class,
        PermanentNegativeCompletion::class,
    ];

    /** @var Status */
    private $currentStatus;

    /** @var Factory */
    private $replyFactory;

    /** @var Logger */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Address */
    private $clientAddress;

    public function __construct(Factory $replyFactory, Logger $logger, Socket $connection, Address $clientAddress)
    {
        $this->replyFactory  = $replyFactory;
        $this->logger        = $logger;
        $this->connection    = $connection;
        $this->clientAddress = $clientAddress;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendEhlo();

            while ($this->currentStatus->getValue() === Status::SENT_EHLO) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendEhlo(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_EHLO);

        $this->connection->write(new Ehlo($this->clientAddress));

        return new Success();
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processEhloSupported($reply);

            case TransientNegativeCompletion::class:
                return $this->processClosingConnection();

            case PermanentNegativeCompletion::class:
                return $this->fallbackToHelo();
        }
    }

    private function processEhloSupported(Reply $reply): Promise
    {
        if ($reply->isLastLine()) {
            $this->currentStatus = new Status(Status::COMPLETED);

            return new Success();
        }

        $this->currentStatus = new Status(Status::PROCESSING_EXTENSION_LIST);

        return new Success();
    }

    private function processClosingConnection(): Promise
    {
        $this->logger->error('Could not process EHLO response');

        throw new TransmissionChannelClosed();
    }

    private function fallbackToHelo(): Promise
    {
        $this->currentStatus = new Status(Status::SEND_HELO);

        return new Success();
    }
}
