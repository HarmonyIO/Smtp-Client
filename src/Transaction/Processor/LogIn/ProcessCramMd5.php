<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\LogIn;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Response;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Start;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use function Amp\call;

class ProcessCramMd5 implements Processor
{
    private const ALLOWED_REPLIES = [
        Status::AWAITING_CRAM_MD5_CHALLENGE => [
            PositiveIntermediate::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENT_CRAM_MD5_RESPONSE => [
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
            yield $this->startCramMd5Process();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES[$this->currentStatus->getValue()]);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                switch ($this->currentStatus->getValue()) {
                    case Status::AWAITING_CRAM_MD5_CHALLENGE:
                        yield $this->processChallengeReply($reply);
                        break;

                    case Status::SENT_CRAM_MD5_RESPONSE:
                        yield $this->processResponseReply($reply);
                        break;
                }
            }

            return $this->currentStatus;
        });
    }

    private function startCramMd5Process(): Promise
    {
        $this->currentStatus = new Status(Status::AWAITING_CRAM_MD5_CHALLENGE);

        $this->connection->write(new AuthCramMd5Start());

        return new Success();
    }

    private function processChallengeReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveIntermediate::class:
                return $this->processCramMd5Challenge($reply);

            case TransientNegativeCompletion::class:
                return $this->processUnknownError();

            case PermanentNegativeCompletion::class:
                return $this->processInvalidCredentials();
        }
    }

    private function processResponseReply(Reply $reply): Promise
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

    private function processCramMd5Challenge(Reply $reply): Promise
    {
        $this->currentStatus = new Status(Status::SENT_CRAM_MD5_RESPONSE);

        return $this->connection->write(new AuthCramMd5Response($this->authentication, $reply->getText()));
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
