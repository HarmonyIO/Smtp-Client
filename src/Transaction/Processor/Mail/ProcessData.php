<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\Data;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessData implements Processor
{
    private const ALLOWED_REPLIES = [
        PositiveIntermediate::class,
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

    public function __construct(Factory $replyFactory, Output $logger, Socket $connection)
    {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendDataCommand();

            while ($this->currentStatus->getValue() !== Status::SENDING_HEADERS) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendDataCommand(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_DATA);

        return $this->connection->write(new Data());
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveIntermediate::class:
                return $this->processDataAccepted();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processDataCommandNotAccepted($reply);
        }
    }

    private function processDataAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::SENDING_HEADERS);

        return new Success();
    }

    private function processDataCommandNotAccepted(Reply $reply): Promise
    {
        $this->connection->write(new Quit());

        throw new DataNotAccepted($reply->getText());
    }
}
