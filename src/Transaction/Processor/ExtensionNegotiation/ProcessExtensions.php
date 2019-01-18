<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\StartTls;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

class ProcessExtensions implements Processor
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

    /** @var Collection */
    private $extensions;

    public function __construct(Factory $replyFactory, Logger $logger, Collection $extensions)
    {
        $this->currentStatus = new Status(Status::PROCESSING_EXTENSION_LIST);

        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->extensions   = $extensions;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            while (!in_array($this->currentStatus->getValue(), [Status::PROCESS_STARTTLS, Status::COMPLETED], true)) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->addExtensionIfSupported($reply);

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processClosingConnection();
        }
    }

    private function addExtensionIfSupported(Reply $reply): Promise
    {
        $this->extensions->enable($reply);

        if (!$reply->isLastLine()) {
            return new Success();
        }

        if ($this->extensions->isExtensionEnabled(StartTls::class)) {
            $this->currentStatus = new Status(Status::PROCESS_STARTTLS);

            return new Success();
        }

        $this->currentStatus = new Status(Status::COMPLETED);

        return new Success();
    }

    private function processClosingConnection(): Promise
    {
        $this->logger->error('Could not process extension response');

        throw new TransmissionChannelClosed();
    }
}
