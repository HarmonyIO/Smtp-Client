<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidMailFromAddress;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Command\MailFrom;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessMailFrom implements Processor
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

    /** @var Address */
    private $fromAddress;

    public function __construct(Factory $replyFactory, Output $logger, Socket $connection, Address $fromAddress)
    {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->fromAddress  = $fromAddress;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendMailFrom();

            while ($this->currentStatus->getValue() === Status::SENT_MAIL_FROM) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendMailFrom(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_MAIL_FROM);

        return $this->connection->write(new MailFrom($this->fromAddress));
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processMailFromAccepted();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processMailFromNotAccepted($reply);
        }
    }

    private function processMailFromAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::SENDING_RECIPIENTS);

        return new Success();
    }

    private function processMailFromNotAccepted(Reply $reply): Promise
    {
        $this->connection->write(new Quit());

        throw new InvalidMailFromAddress($reply->getText());
    }
}
