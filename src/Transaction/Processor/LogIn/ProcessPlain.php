<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\LogIn;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\AuthPlain;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use function Amp\call;

class ProcessPlain implements Processor
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

    /** @var Authentication */
    private $authentication;

    public function __construct(Factory $replyFactory, Output $logger, Socket $connection, Authentication $authentication)
    {
        $this->replyFactory   = $replyFactory;
        $this->logger         = $logger;
        $this->connection     = $connection;
        $this->authentication = $authentication;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->sendPlain();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function sendPlain(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_PLAIN);

        $this->connection->write((string) new AuthPlain($this->authentication));

        return new Success();
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processCredentialsAccepted();

            case TransientNegativeCompletion::class:
                return $this->processUnknownError();

            case PermanentNegativeCompletion::class:
                return $this->processInvalidCredentials();
        }
    }

    private function processCredentialsAccepted(): Promise
    {
        $this->currentStatus = new Status(Status::COMPLETED);

        return new Success();
    }

    public function processUnknownError(): Promise
    {
        throw new TransmissionChannelClosed();
    }

    private function processInvalidCredentials(): Promise
    {
        throw new InvalidCredentials();
    }
}
