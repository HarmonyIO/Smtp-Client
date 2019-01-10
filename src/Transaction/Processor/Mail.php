<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\BodyLine;
use HarmonyIO\SmtpClient\Transaction\Command\Data;
use HarmonyIO\SmtpClient\Transaction\Command\EndData;
use HarmonyIO\SmtpClient\Transaction\Command\Header as HeaderCommand;
use HarmonyIO\SmtpClient\Transaction\Command\HeadersAndBodySeparator;
use HarmonyIO\SmtpClient\Transaction\Command\MailFrom;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use HarmonyIO\SmtpClient\Transaction\Command\RcptTo;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

final class Mail implements Processor
{
    private const AVAILABLE_COMMANDS = [
        Status::SENT_MAIL_FROM => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENDING_RECIPIENTS => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENT_DATA => [
            PositiveIntermediate::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENT_CONTENT => [
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

    /** @var Envelop */
    private $envelop;

    /** @var Address[] */
    private $recipientsToSend = [];

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Envelop $envelop
    ) {
        $this->replyFactory        = $replyFactory;
        $this->logger              = $logger;
        $this->connection          = $connection;
        $this->envelop             = $envelop;
        $this->recipientsToSend    = $envelop->getRecipients();
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            $this->sendMailFrom();

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
            case Status::SENT_MAIL_FROM:
                $this->processSentEhloReply($reply);
                return;

            case Status::SENDING_RECIPIENTS:
                $this->processSendingRecipientsReply($reply);
                return;

            case Status::SENT_DATA:
                $this->processSentDataReply($reply);
                return;

            case Status::SENT_CONTENT:
                $this->processSentContentReply($reply);
                return;
        }
    }

    private function processSentEhloReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processMailFromAccepted();
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processMailFromNotAccepted($reply);
                return;
        }
    }

    private function processSendingRecipientsReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processRecipientAccepted();
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processRecipientNotAccepted();
                return;
        }
    }

    private function processSentDataReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveIntermediate::class:
                $this->processDataAccepted();
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processRecipientNotAccepted();
                return;
        }
    }

    private function processSentContentReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processContentAccepted();
                return;

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                $this->processContentNotAccepted($reply);
                return;
        }
    }

    private function sendMailFrom(): void
    {
        $this->currentStatus = new Status(Status::SENT_MAIL_FROM);

        $this->connection->write((string) new MailFrom($this->envelop->getMailFromAddress()));
        $this->processMailFromAccepted();
    }

    private function processMailFromNotAccepted(Reply $reply): void
    {
        $this->connection->write((string) new Quit());

        throw new InvalidMailFromAddress((string) $reply->getText());
    }

    private function processMailFromAccepted(): void
    {
        $this->currentStatus = new Status(Status::SENDING_RECIPIENTS);

        $recipient = array_shift($this->recipientsToSend);

        $this->connection->write((string) new RcptTo($recipient));
    }

    private function processRecipientAccepted(): void
    {
        if (!count($this->recipientsToSend)) {
            $this->sendData();

            return;
        }

        $recipient = array_shift($this->recipientsToSend);

        $this->connection->write((string) new RcptTo($recipient));
    }

    private function processRecipientNotAccepted(): void
    {
        // @todo: log (and mark?) recipient as invalid, but continue
        //        check whether we have at least one valid recipient before starting DATA

        if (!count($this->recipientsToSend)) {
            $this->sendData();

            return;
        }
    }

    private function sendData(): void
    {
        $this->currentStatus = new Status(Status::SENT_DATA);

        $this->connection->write((string) new Data());
    }

    private function processDataAccepted(): void
    {
        $this->currentStatus = new Status(Status::SENT_CONTENT);

        foreach ($this->envelop->getHeaders() as $header) {
            $this->connection->write((string) new HeaderCommand($header));
        }

        $this->connection->write((string) new HeadersAndBodySeparator());

        $this->connection->write((string) new BodyLine($this->envelop->getBody()));

        $this->connection->write((string) new EndData());
    }

    private function processContentAccepted(): void
    {
        $this->currentStatus = new Status(Status::COMPLETED);

        $this->connection->write((string) new Quit());
    }

    private function processContentNotAccepted(Reply $reply): void
    {
        throw new DataNotAccepted($reply->getText());
    }
}
