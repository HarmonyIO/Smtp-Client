<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\ClientAddress\Address;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Command\Helo;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

class ProcessHelo implements Processor
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
            yield $this->sendHelo();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendHelo(): Promise
    {
        $this->currentStatus = new Status(Status::SEND_HELO);

        $this->connection->write(new Helo($this->clientAddress));

        return new Success();
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processHeloSupported();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processClosingConnection();
        }
    }

    private function processHeloSupported(): Promise
    {
        $this->currentStatus = new Status(Status::COMPLETED);

        return new Success();
    }

    private function processClosingConnection(): Promise
    {
        throw new TransmissionChannelClosed();
    }
}
