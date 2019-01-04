<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse;

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
