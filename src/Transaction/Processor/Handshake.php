<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\TransactionFailed as TransactionFailedException;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Handshake as Status;
use function Amp\call;

final class Handshake implements Processor
{
    private const AVAILABLE_COMMANDS = [
        Status::AWAITING_GREETING => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
        ],
        Status::AWAITING_BANNER => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
        ],
    ];

    /** @var Status */
    private $currentStatus;

    /** @var Factory */
    private $replyFactory;

    /** @var Output */
    private $logger;

    public function __construct(Factory $replyFactory, Output $logger)
    {
        $this->currentStatus = new Status(Status::AWAITING_GREETING);

        $this->replyFactory  = $replyFactory;
        $this->logger        = $logger;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
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
            case Status::AWAITING_GREETING:
                $this->processAwaitingGreetingReply($reply);
                return;

            case Status::AWAITING_BANNER:
                $this->processAwaitingBannerReply($reply);
                return;
        }
    }

    private function processAwaitingGreetingReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processGreeting($reply);
                return;

            case TransientNegativeCompletion::class:
                $this->processFailedTransaction($reply);
                return;
        }
    }

    private function processAwaitingBannerReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processBanner($reply);
                return;

            case TransientNegativeCompletion::class:
                $this->processFailedTransaction($reply);
                return;
        }
    }

    private function processGreeting(Reply $reply): void
    {
        if ($reply->isLastLine()) {
            $this->currentStatus = new Status(Status::COMPLETED);

            return;
        }

        $this->currentStatus = new Status(Status::AWAITING_BANNER);
    }

    private function processFailedTransaction(Reply $reply): void
    {
        throw new TransactionFailedException((string) $reply->getText());
    }

    private function processBanner(Reply $reply): void
    {
        if (!$reply->isLastLine()) {
            return;
        }

        $this->currentStatus = new Status(Status::COMPLETED);
    }
}
