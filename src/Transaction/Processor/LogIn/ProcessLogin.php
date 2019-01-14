<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\LogIn;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginPassword;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginStart;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginUsername;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use function Amp\call;

class ProcessLogin implements Processor
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
            yield $this->startLoginProcess();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function startLoginProcess(): Promise
    {
        $this->currentStatus = new Status(Status::SENT_LOGIN);

        $this->connection->write((string) new AuthLoginStart());

        return new Success();
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->processCredentialsAccepted();

            case PositiveIntermediate::class:
                return $this->processActiveLoginProcess($reply);

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

    private function processActiveLoginProcess(Reply $reply): Promise
    {
        switch (substr(strtolower(base64_decode($reply->getText())), 0, 8)) {
            case 'username':
                return $this->processActiveLoginSendUsername();

            case 'password':
                return $this->processActiveLoginSendPassword();

            default:
                throw new UnexpectedReply((string) $reply);
        }
    }

    private function processActiveLoginSendUsername(): Promise
    {
        return $this->connection->write((string) new AuthLoginUsername($this->authentication));
    }

    private function processActiveLoginSendPassword(): Promise
    {
        return $this->connection->write((string) new AuthLoginPassword($this->authentication));
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
