<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Handshake;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\TransactionFailed;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Handshake as Status;
use function Amp\call;

class ProcessBanner implements Processor
{
    private const ALLOWED_REPLIES = [
        PositiveCompletion::class,
        TransientNegativeCompletion::class,
    ];

    /** @var Status */
    private $currentStatus;

    /** @var Factory */
    private $replyFactory;

    /** @var Logger */
    private $logger;

    public function __construct(Factory $replyFactory, Logger $logger)
    {
        $this->currentStatus = new Status(Status::PROCESS_BANNER);

        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
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
                return $this->processBanner($reply);

            case TransientNegativeCompletion::class:
                return $this->failTransaction($reply);
        }
    }

    private function processBanner(Reply $reply): Promise
    {
        if (!$reply->isLastLine()) {
            return new Success();
        }

        return $this->completeProcess();
    }

    private function failTransaction(Reply $reply): Promise
    {
        $this->logger->error('Could not process SMTP banner');

        throw new TransactionFailed((string) $reply->getText());
    }

    private function completeProcess(): Promise
    {
        $this->currentStatus = new Status(Status::COMPLETED);

        return new Success();
    }
}
