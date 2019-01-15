<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\DataNotAccepted;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\BodyLine;
use HarmonyIO\SmtpClient\Transaction\Command\EndData;
use HarmonyIO\SmtpClient\Transaction\Command\Quit;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessContent implements Processor
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

    /** @var string */
    private $body;

    public function __construct(Factory $replyFactory, Output $logger, Socket $connection, string $body)
    {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->body         = $body;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->processContent();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function processContent(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_CONTENT);

        $this->connection->write(new BodyLine($this->body));

        return $this->connection->write(new EndData());
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processContentAccepted();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processContentNotAccepted($reply);
        }
    }

    private function processContentAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::COMPLETED);

        return $this->connection->write(new Quit());
    }

    private function processContentNotAccepted(Reply $reply): Promise
    {
        $this->connection->write(new Quit());

        throw new DataNotAccepted($reply->getText());
    }
}
