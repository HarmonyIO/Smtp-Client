<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;

use Amp\Promise;
use Amp\Socket\ClientTlsContext;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Exception\Smtp\CouldNotUpgradeConnection;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Command\StartTls;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Reply\PermanentNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\PositiveCompletion;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use HarmonyIO\SmtpClient\Transaction\Reply\TransientNegativeCompletion;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

class ProcessStartTls implements Processor
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

    public function __construct(Factory $replyFactory, Output $logger, Socket $connection)
    {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            yield $this->startTls();

            while ($this->currentStatus->getValue() !== Status::START_TLS_PROCESS) {
                $line  = yield $buffer->readLine();
                $reply = $this->replyFactory->build($line, self::ALLOWED_REPLIES);

                $this->logger->debug('Server reply object: ' . get_class($reply));

                yield $this->processReply($reply);
            }

            return $this->currentStatus;
        });
    }

    private function startTls(): Promise
    {
        $this->currentStatus = new Status(Status::STARTING_TLS);

        return $this->connection->write(new StartTls());
    }

    private function processReply(Reply $reply): Promise
    {
        switch (get_class($reply)) {
            case PositiveCompletion::class:
                return $this->enableCrypto();

            case TransientNegativeCompletion::class:
            case PermanentNegativeCompletion::class:
                return $this->processUnsupportedStartTls($reply);
        }
    }

    private function enableCrypto(): Promise
    {
        $this->currentStatus = new Status(Status::START_TLS_PROCESS);

        $tlsContext = (new ClientTlsContext())
            ->withoutPeerVerification()
        ;

        return $this->connection->enableCrypto($tlsContext);
    }

    private function processUnsupportedStartTls(Reply $reply): Promise
    {
        throw new CouldNotUpgradeConnection($reply->getText());
    }
}
