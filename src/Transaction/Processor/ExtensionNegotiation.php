<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\ClientAddress\Address;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessEhlo;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessExtensions;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessHelo;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation\ProcessStartTls;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\ExtensionNegotiation as Status;
use function Amp\call;

final class ExtensionNegotiation implements Processor
{
    /** @var Factory */
    private $replyFactory;

    /** @var Output */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Address */
    private $clientAddress;

    /** @var Collection */
    private $extensionCollection;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Address $clientAddress,
        Collection $extensionCollection
    ) {
        $this->replyFactory        = $replyFactory;
        $this->logger              = $logger;
        $this->connection          = $connection;
        $this->clientAddress       = $clientAddress;
        $this->extensionCollection = $extensionCollection;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            $status = new Status(Status::START_PROCESS);

            $processors = [
                new ProcessEhlo($this->replyFactory, $this->logger, $this->connection, $this->clientAddress),
                new ProcessHelo($this->replyFactory, $this->logger, $this->connection, $this->clientAddress),
                new ProcessExtensions($this->replyFactory, $this->logger, $this->extensionCollection),
                new ProcessStartTls($this->replyFactory, $this->logger, $this->connection),
            ];

            /** @var Processor $processor */
            foreach ($processors as $processor) {
                if (get_class($processor) === ProcessHelo::class && $status->getValue() !== Status::SEND_HELO) {
                    continue;
                }

                /** @var Status $status */
                $status = yield $processor->process($buffer);

                if ($status->getValue() === Status::START_TLS_PROCESS) {
                    $this->extensionCollection->clearEnabledExtensions();

                    // process the entire transaction again over the secure channel
                    return $this->process($buffer);
                }

                if ($status->getValue() === Status::COMPLETED) {
                    return;
                }
            }
        });
    }
}
