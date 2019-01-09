<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\Ehlo;
use HarmonyIO\SmtpClient\Transaction\Command\Helo;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

final class ExtensionNegotiation implements Processor
{
    private const AVAILABLE_COMMANDS = [
        Status::SENT_EHLO => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::PROCESSING_EXTENSION_LIST => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENT_HELO => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
    ];

    /** @var Status */
    private $currentStatus;

    /** @var Factory */
    private $replyFactory;

    /** @var Output */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Address */
    private $clientAddress;

    /** @var Collection */
    private $extensionCollection;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Address $clientAddress,
        Collection $extensionCollection
    ) {
        $this->replyFactory        = $replyFactory;
        $this->logger              = $logger;
        $this->connection          = $connection;
        $this->clientAddress       = $clientAddress;
        $this->extensionCollection = $extensionCollection;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            $this->sendEhlo();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $this->processReply(yield $buffer->readLine());
            }
        });
    }

    private function processReply(string $line): void
    {
        $reply = $this->replyFactory->build($line, self::AVAILABLE_COMMANDS[$this->currentStatus->getValue()]);

        $this->logger->debug('Server reply object: ' . get_class($reply));

        switch ($this->currentStatus->getValue()) {
            case Status::SENT_EHLO:
                $this->processSentEhloReply($reply);
                return;

            case Status::PROCESSING_EXTENSION_LIST:
                $this->processExtensionListReply($reply);
                return;

            case Status::SENT_HELO:
                $this->processSentHeloReply($reply);
                return;
        }
    }

    private function processSentEhloReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processEhloSupported($reply);
                return;

            case TransientNegativeCompletion::class:
                $this->processClosingConnection();
                return;

            case PermanentNegativeCompletion::class:
                $this->fallbackToHelo();
                return;
        }
    }

    private function processExtensionListReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->addExtensionIfSupported($reply);
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processClosingConnection();
                return;
        }
    }

    private function processSentHeloReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processHeloSupported();
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processClosingConnection();
                return;
        }
    }

    private function sendEhlo(): void
    {
        $this->currentStatus = new Status(Status::SENT_EHLO);

        $this->connection->write((string) new Ehlo($this->clientAddress));
    }

    private function processEhloSupported(Reply $reply): void
    {
        if ($reply->isLastLine()) {
            $this->currentStatus = new Status(Status::COMPLETED);

            return;
        }

        $this->currentStatus = new Status(Status::PROCESSING_EXTENSION_LIST);
    }

    private function processClosingConnection(): void
    {
        throw new TransmissionChannelClosed();
    }

    private function fallbackToHelo(): void
    {
        $this->currentStatus = new Status(Status::SENT_HELO);

        $this->connection->write((string) new Helo($this->clientAddress));
    }

    private function processHeloSupported(): void
    {
        $this->currentStatus = new Status(Status::COMPLETED);
    }

    private function addExtensionIfSupported(Reply $reply): void
    {
        if ($reply->isLastLine()) {
            $this->currentStatus = new Status(Status::COMPLETED);

            return;
        }

        $this->extensionCollection->enable($reply);
    }
}
