<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidCredentials;
use HarmonyIO\SmtpClient\Exception\Smtp\TransmissionChannelClosed;
use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Response;
use HarmonyIO\SmtpClient\Transaction\Command\AuthCramMd5Start;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginPassword;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginStart;
use HarmonyIO\SmtpClient\Transaction\Command\AuthLoginUsername;
use HarmonyIO\SmtpClient\Transaction\Command\AuthPlain;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveIntermediate;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\LogIn as Status;
use function Amp\call;

final class LogIn implements Processor
{
    private const AVAILABLE_COMMANDS = [
        Status::SENT_PLAIN => [
            PositiveCompletion::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
        Status::SENT_LOGIN => [
            PositiveCompletion::class,
            PositiveIntermediate::class,
            TransientNegativeCompletion::class,
            PermanentNegativeCompletion::class,
        ],
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

    /** @var Collection */
    private $extensionCollection;

    /** @var Authentication|null */
    private $authentication;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Collection $extensionCollection,
        ?Authentication $authentication = null
    ) {
        $this->replyFactory        = $replyFactory;
        $this->logger              = $logger;
        $this->connection          = $connection;
        $this->extensionCollection = $extensionCollection;
        $this->authentication      = $authentication;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            if ($this->authentication === null) {
                return;
            }

            if (!$this->extensionCollection->isExtensionEnabled(Auth::class)) {
                return;
            }

            $this->requestLogIn();

            while ($this->currentStatus->getValue() !== Status::COMPLETED) {
                $this->processReply(yield $buffer->readLine());
            }
        });
    }

    private function requestLogIn(): void
    {
        /** @var Auth $authExtension */
        $authExtension = $this->extensionCollection->getExtension(Auth::class);

        switch ($authExtension->getPreferredAuthenticationMethod()) {
            case 'PLAIN':
                $this->startPlain();
                return;

            case 'LOGIN':
                $this->startLogin();
                return;

            case 'CRAM-MD5':
                $this->startCramMd5();
                return;
        }
    }

    private function processReply(string $line): void
    {
        $reply = $this->replyFactory->build($line, self::AVAILABLE_COMMANDS[$this->currentStatus->getValue()]);

        $this->logger->debug('Server reply object: ' . get_class($reply));

        switch ($this->currentStatus->getValue()) {
            case Status::SENT_PLAIN:
                $this->processSentPlainReply($reply);
                return;

            case Status::SENT_LOGIN:
                $this->processSentLoginReply($reply);
                return;

            case Status::AWAITING_CRAM_MD5_CHALLENGE:
                $this->processAwaitingCramMd5Reply($reply);
                return;

            case Status::SENT_CRAM_MD5_RESPONSE:
                $this->processSentCramMd5ResponseReply($reply);
                return;
        }
    }

    private function processSentPlainReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processCredentialsAccepted();
                return;

            case TransientNegativeCompletion::class:
                $this->processUnknownError();
                return;

            case PermanentNegativeCompletion::class:
                $this->processInvalidCredentials();
                return;
        }
    }

    private function processSentLoginReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processCredentialsAccepted();
                return;

            case PositiveIntermediate::class:
                $this->processActiveLoginProcess($reply);
                return;

            case TransientNegativeCompletion::class:
                $this->processUnknownError();
                return;

            case PermanentNegativeCompletion::class:
                $this->processInvalidCredentials();
                return;
        }
    }

    private function processAwaitingCramMd5Reply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveIntermediate::class:
                $this->processCramMd5Challenge($reply);
                return;

            case TransientNegativeCompletion::class:
                $this->processUnknownError();
                return;

            case PermanentNegativeCompletion::class:
                $this->processInvalidCredentials();
                return;
        }
    }

    private function processSentCramMd5ResponseReply(Reply $reply): void
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                $this->processCredentialsAccepted();
                return;

            case TransientNegativeCompletion::class:
                $this->processUnknownError();
                return;

            case PermanentNegativeCompletion::class:
                $this->processInvalidCredentials();
                return;
        }
    }

    private function startPlain(): void
    {
        $this->currentStatus = new Status(Status::SENT_PLAIN);

        $this->connection->write((string) new AuthPlain($this->authentication));
    }

    private function startLogin(): void
    {
        $this->currentStatus = new Status(Status::SENT_LOGIN);

        $this->connection->write((string) new AuthLoginStart());
    }

    private function startCramMd5(): void
    {
        $this->currentStatus = new Status(Status::AWAITING_CRAM_MD5_CHALLENGE);

        $this->connection->write((string) new AuthCramMd5Start());
    }

    private function processActiveLoginProcess(Reply $reply): void
    {
        switch (substr(strtolower(base64_decode($reply->getText())), 0, 8)) {
            case 'username':
                $this->processActiveLoginSendUsername();
                return;

            case 'password':
                $this->processActiveLoginSendPassword();
                return;

            default:
                throw new UnexpectedReply((string) $reply);
        }
    }

    private function processActiveLoginSendUsername(): void
    {
        $this->connection->write((string) new AuthLoginUsername($this->authentication));
    }

    private function processActiveLoginSendPassword(): void
    {
        $this->connection->write((string) new AuthLoginPassword($this->authentication));
    }

    private function processCramMd5Challenge(Reply $reply): void
    {
        $this->currentStatus = new Status(Status::SENT_CRAM_MD5_RESPONSE);

        $this->connection->write((string) new AuthCramMd5Response($this->authentication, $reply->getText()));
    }

    private function processCredentialsAccepted(): void
    {
        $this->currentStatus = new Status(Status::COMPLETED);
    }

    public function processUnknownError(): void
    {
        throw new TransmissionChannelClosed();
    }

    private function processInvalidCredentials(): void
    {
        throw new InvalidCredentials();
    }
}
