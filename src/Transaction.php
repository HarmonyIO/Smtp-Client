<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Command\Auth\CramMd5Response;
use HarmonyIO\SmtpClient\Command\Auth\LogInPassword;
use HarmonyIO\SmtpClient\Command\Auth\LogInUsername;
use HarmonyIO\SmtpClient\Command\Auth\Plain;
use HarmonyIO\SmtpClient\Command\Auth\StartCramMd5;
use HarmonyIO\SmtpClient\Command\Auth\StartLogIn;
use HarmonyIO\SmtpClient\Command\Ehlo;
use HarmonyIO\SmtpClient\Command\Helo;
use HarmonyIO\SmtpClient\Command\Quit;
use HarmonyIO\SmtpClient\Exception\InvalidCredentials as InvalidCredentialsException;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerCapabilities\Collection;
use HarmonyIO\SmtpClient\ServerResponse\Connect\ServiceReady;
use HarmonyIO\SmtpClient\ServerResponse\Factory as ServerResponseFactory;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\Authentication as AuthenticationCapability;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\DeliveryStatusNotification;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\MessageSizeDeclaration;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\UnsupportedExtension;
use HarmonyIO\SmtpClient\ServerResponse\Response;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\EhloResponse;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\InvalidCommand;
use HarmonyIO\SmtpClient\ServerResponse\StartedCramMd5Auth\Challenge;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\AcceptedCredentials;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\InvalidCredentials;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\Password;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\Username;

class Transaction
{
    /** @var Output */
    private $logger;

    /** @var Socket */
    private $socket;

    /** @var ServerResponseFactory */
    private $serverResponseFactory;

    /** @var Authentication|null */
    private $authentication;

    /** @var TransactionStatus */
    private $status;

    /** @var Collection */
    private $smtpCapabilities;

    public function __construct(
        Output $logger,
        Socket $socket,
        ServerResponseFactory $serverResponseFactory,
        ?Authentication $authentication = null
    ) {
        $this->logger                = $logger;
        $this->socket                = $socket;
        $this->serverResponseFactory = $serverResponseFactory;
        $this->authentication        = $authentication;

        $this->status           = TransactionStatus::CONNECT();
        $this->smtpCapabilities = new Collection();
    }

    public function processLine(string $line): void
    {
        $this->logger->debug('Current client status is: ' . $this->status->getKey());

        $this->logger->smtpIn($line);

        try {
            $serverResponse = $this->serverResponseFactory->build($this->status, $line);
        } catch (\Throwable $e) {
            // @todo: should we quit here? Or throw? Or both?
            return;
        }

        $this->logger->debug('Server response object: ' . get_class($serverResponse));

        switch (get_class($serverResponse)) {
            case ServiceReady::class:
                $this->processServiceAvailability();
                return;

            case EhloResponse::class:
                /** @var EhloResponse $serverResponse */
                $this->processEhloSupported($serverResponse);
                return;

            case InvalidCommand::class:
                $this->processEhloNotSupported();
                return;

            case DeliveryStatusNotification::class:
                /** @var DeliveryStatusNotification $serverResponse */
                $this->processDsnCapability($serverResponse);
                return;

            case MessageSizeDeclaration::class:
                /** @var MessageSizeDeclaration $serverResponse */
                $this->processSizeCapability($serverResponse);
                return;

            case AuthenticationCapability::class:
                /** @var AuthenticationCapability $serverResponse */
                $this->processAuthenticationCapability($serverResponse);
                return;

            case UnsupportedExtension::class:
                $this->processUnsupportedCapability($serverResponse);
                return;

            case Username::class:
                $this->processLogInUsername();
                return;

            case Password::class:
                $this->processLogInPassword();
                return;

            case InvalidCredentials::class:
                $this->processInvalidLogInCredentials();
                return;

            case AcceptedCredentials::class:
                $this->processValidLogInCredentials();
                return;

            case Challenge::class:
                /** @var Challenge $serverResponse */
                $this->processCramMd5Challenge($serverResponse);
                return;
        }
    }

    private function processServiceAvailability(): void
    {
        $this->status = TransactionStatus::SENT_EHLO();

        $this->socket->write((string) new Ehlo('foo.bar'));
    }

    private function processEhloSupported(EhloResponse $ehloResponse): void
    {
        if ($ehloResponse->isLastResponse()) {
            $this->status = TransactionStatus::PROCESSING_EHLO();

            return;
        }

        $this->status = TransactionStatus::PROCESSING_EHLO();
    }

    private function processEhloNotSupported(): void
    {
        $this->status = TransactionStatus::SENT_HELO();

        $this->socket->write((string) new Helo('foo.bar'));
    }

    private function processDsnCapability(DeliveryStatusNotification $dsnCapability): void
    {
        $this->smtpCapabilities->addCapability($dsnCapability);

        $this->keepProcessingEhloHandShakeOrContinueTheMailProcess($dsnCapability);
    }

    private function processSizeCapability(MessageSizeDeclaration $messageSizeDeclaration): void
    {
        $this->smtpCapabilities->addCapability($messageSizeDeclaration);

        $this->keepProcessingEhloHandShakeOrContinueTheMailProcess($messageSizeDeclaration);
    }

    private function processAuthenticationCapability(AuthenticationCapability $authentication): void
    {
        $this->smtpCapabilities->addCapability($authentication);

        $this->keepProcessingEhloHandShakeOrContinueTheMailProcess($authentication);
    }

    private function processUnsupportedCapability(Response $response): void
    {
        $this->keepProcessingEhloHandShakeOrContinueTheMailProcess($response);
    }

    private function keepProcessingEhloHandShakeOrContinueTheMailProcess(Response $response): void
    {
        if (!$response->isLastResponse()) {
            return;
        }

        if ($this->authentication !== null && $this->smtpCapabilities->isCapableOf(AuthenticationCapability::class)) {
            $this->startAuthentication();

            return;
        }
    }

    private function startAuthentication(): void
    {
        /** @var AuthenticationCapability $authentication */
        $authentication = $this->smtpCapabilities->getCapability(AuthenticationCapability::class);

        switch ($authentication->getPreferredAuthenticationMethod()) {
            case 'PLAIN':
                $this->startPlainAuthentication();
                return;

            case 'LOGIN':
                $this->startLogInAuthentication();
                return;

            case 'CRAM-MD5':
                $this->startCramMd5Authentication();
                return;
        }

        $this->status = TransactionStatus::SENT_HELO();
    }

    private function startPlainAuthentication(): void
    {
        $this->status = TransactionStatus::STARTED_PLAIN_AUTH();

        $this->socket->write((string) new Plain($this->authentication));
    }

    private function startLogInAuthentication(): void
    {
        $this->status = TransactionStatus::STARTED_LOGIN_AUTH();

        $this->socket->write((string) new StartLogIn());
    }

    private function processLogInUsername(): void
    {
        $this->socket->write((string) new LogInUsername($this->authentication));
    }

    private function processLogInPassword(): void
    {
        $this->socket->write((string) new LogInPassword($this->authentication));
    }

    private function startCramMd5Authentication(): void
    {
        $this->status = TransactionStatus::STARTED_CRAM_MD5_AUTH();

        $this->socket->write((string) new StartCramMd5());
    }

    private function processCramMd5Challenge(Challenge $challenge): void
    {
        $this->socket->write((string) new CramMd5Response($this->authentication, $challenge));
    }

    private function processInvalidLogInCredentials(): void
    {
        $this->socket->write((string) new Quit());

        throw new InvalidCredentialsException();
    }

    private function processValidLogInCredentials(): void
    {
        var_dump('ACCEPTED CREDENTIALS \o/');
        // continue smtp flow
    }
}
