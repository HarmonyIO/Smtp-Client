<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\RcptTo;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessRecipients implements Processor
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

    /** @var Output */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Address[] */
    private $recipients;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Address $recipient,
        Address ...$recipients
    ) {
        $this->currentStatus = new Status(Status::SENDING_RECIPIENTS);

        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->recipients   = array_merge([$recipient], $recipients);
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendRecipient();

            while ($this->currentStatus->getValue() !== Status::SENT_RECIPIENTS) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendRecipient(): Promise
    {
        $recipient = array_shift($this->recipients);

        return $this->connection->write(new RcptTo($recipient));
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processRecipientAccepted();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processRecipientNotAccepted();
        }
    }

    private function processRecipientAccepted(): Promise
    {
        if ($this->recipients) {
            return $this->sendRecipient();
        }

        $this->currentStatus = new Status(Status::SENT_RECIPIENTS);

        return new Success();
    }

    private function processRecipientNotAccepted(): Promise
    {
        // @todo: log (and mark?) recipient as invalid, but continue
        //        check whether we have at least one valid recipient before starting DATA

        if ($this->recipients) {
            return $this->sendRecipient();
        }

        $this->currentStatus = new Status(Status::SENT_RECIPIENTS);

        return new Success();
    }
}
