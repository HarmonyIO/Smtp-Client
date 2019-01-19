<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;
use HarmonyIO\SmtpClient\Exception\Smtp\NoRecipientsAccepted;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Command\Data;
use HarmonyIO\SmtpClient\Transaction\Command\MailFrom;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use HarmonyIO\SmtpClient\Transaction\Command\RcptTo;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessPipelining implements Processor
{
    private const ALLOWED_REPLIES = [
        PositiveCompletion::class,
        PositiveIntermediate::class,
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
    private $fromAddress;

    /** @var Address[] */
    private $recipients;

    /** @var int */
    private $numberOfRecipientsToProcess;

    /** @var int */
    private $numberOfAcceptedRecipients = 0;

    public function __construct(
        Factory $replyFactory,
        Logger $logger,
        Socket $connection,
        Address $fromAddress,
        Address $recipient,
        Address ...$recipients
    ) {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->fromAddress  = $fromAddress;
        $this->recipients   = array_merge([$recipient], $recipients);

        $this->numberOfRecipientsToProcess = count($this->recipients);
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendMailInformation();

            while ($this->currentStatus->getValue() !== Status::SENDING_HEADERS) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendMailInformation(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_MAIL_FROM);

        $this->connection->write(new MailFrom($this->fromAddress));

        foreach ($this->recipients as $recipient) {
            $this->connection->write(new RcptTo($recipient));
        }

        return $this->connection->write(new Data());
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
            case PositiveIntermediate::class:
                return $this->processSuccessReply();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processFailureReply($reply);
        }
    }

    private function processSuccessReply(): Promise
    {
        switch ($this->currentStatus->getValue()) {
            case Status::SENT_MAIL_FROM:
                return $this->processMailFromAccepted();

            case Status::SENDING_RECIPIENTS:
                return $this->processRecipientAccepted();

            case Status::SENT_DATA:
                return $this->processDataAccepted();
        }
    }

    private function processFailureReply(Reply $reply): Promise
    {
        switch ($this->currentStatus->getValue()) {
            case Status::SENT_MAIL_FROM:
                return $this->processInvalidMailFromAddress($reply);

            case Status::SENDING_RECIPIENTS:
                return $this->processRecipientDeclined();

            case Status::SENT_DATA:
                return $this->processDataNotAccepted($reply);
        }
    }

    private function processMailFromAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::SENDING_RECIPIENTS);

        return new Success();
    }

    private function processInvalidMailFromAddress(Reply $reply): Promise
    {
        $this->connection->write(new Quit());

        throw new InvalidMailFromAddress($reply->getText());
    }

    private function processRecipientAccepted(): Promise
    {
        $this->numberOfAcceptedRecipients++;

        $this->processHandledRecipient();

        return new Success();
    }

    private function processRecipientDeclined(): Promise
    {
        $this->processHandledRecipient();

        return new Success();
    }

    private function processHandledRecipient(): void
    {
        $this->numberOfRecipientsToProcess--;

        if ($this->numberOfRecipientsToProcess > 0) {
            return;
        }

        if ($this->numberOfAcceptedRecipients === 0) {
            $this->connection->write(new Quit());

            throw new NoRecipientsAccepted();
        }

        $this->currentStatus = new Status(Status::SENT_DATA);
    }

    private function processDataAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::SENDING_HEADERS);

        return new Success();
    }

    private function processDataNotAccepted(Reply $reply): Promise
    {
        $this->connection->write(new Quit());

        throw new DataNotAccepted($reply->getText());
    }
}
