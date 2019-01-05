<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse;

use HarmonyIO\SmtpClient\ServerResponse\Connect\ServiceReady;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\Authentication;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\DeliveryStatusNotification;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\MessageSizeDeclaration;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\Pipelining;
use HarmonyIO\SmtpClient\ServerResponse\ProcessingEhlo\UnsupportedExtension;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\EhloResponse;
use HarmonyIO\SmtpClient\ServerResponse\SentEhlo\InvalidCommand;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\AcceptedCredentials;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\InvalidCredentials;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\Password;
use HarmonyIO\SmtpClient\ServerResponse\StartedLogInAuth\Username;
use HarmonyIO\SmtpClient\TransactionStatus;

class Factory
{
    /** @var Response[] */
    private $availableCommands = [];

    public function __construct()
    {
        $this->availableCommands = [
            TransactionStatus::CONNECT()->getValue() => [
                ServiceReady::class,
            ],
            TransactionStatus::SENT_EHLO()->getValue() => [
                InvalidCommand::class,
                EhloResponse::class,
            ],
            TransactionStatus::PROCESSING_EHLO()->getValue() => [
                DeliveryStatusNotification::class,
                MessageSizeDeclaration::class,
                Pipelining::class,
                Authentication::class,
                // must always be the last in the list
                UnsupportedExtension::class,
            ],
            TransactionStatus::STARTED_PLAIN_AUTH()->getValue() => [
                InvalidCredentials::class,
                AcceptedCredentials::class,
            ],
            TransactionStatus::STARTED_LOGIN_AUTH()->getValue() => [
                Username::class,
                Password::class,
                InvalidCredentials::class,
                AcceptedCredentials::class,
            ],
        ];
    }

    public function build(TransactionStatus $transactionStatus, string $line): Response
    {
        if (!array_key_exists($transactionStatus->getValue(), $this->availableCommands)) {
            // @todo: send QUIT command? At least throw proper exception
            throw new \Exception('Syntax error, unexpected reply');
        }

        /** @var Response $command */
        foreach ($this->availableCommands[$transactionStatus->getValue()] as $command) {
            if (!$command::isValid($line)) {
                continue;
            }

            return new $command($line);
        }

        // @todo: send QUIT command? At least throw proper exception
        throw new \Exception('Syntax error, reply unrecognised');
    }
}
