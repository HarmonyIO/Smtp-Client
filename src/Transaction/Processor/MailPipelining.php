<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use Amp\Success;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\Pipelining;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessContent;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessHeaders;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessPipelining;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

final class MailPipelining implements Processor
{
    /** @var Factory */
    private $replyFactory;

    /** @var Logger */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Envelop */
    private $envelop;

    /** @var Collection */
    private $extensions;

    public function __construct(
        Factory $replyFactory,
        Logger $logger,
        Socket $connection,
        Envelop $envelop,
        Collection $extensions
    ) {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->envelop      = $envelop;
        $this->extensions   = $extensions;
    }

    public function process(Buffer $buffer): Promise
    {
        if (!$this->extensions->isExtensionEnabled(Pipelining::class)) {
            return new Success();
        }

        return call(function () use ($buffer) {
            $processors = [
                new ProcessPipelining($this->replyFactory, $this->logger, $this->connection, $this->envelop->getMailFromAddress(), ...$this->envelop->getRecipients()),
                new ProcessHeaders($this->connection, ...array_values($this->envelop->getHeaders())),
                new ProcessContent($this->replyFactory, $this->logger, $this->connection, $this->envelop->getBody()),
            ];

            /** @var Processor $processor */
            foreach ($processors as $processor) {
                /** @var Status $status */
                $status = yield $processor->process($buffer);

                if ($status->getValue() === Status::COMPLETED) {
                    return;
                }
            }
        });
    }
}
