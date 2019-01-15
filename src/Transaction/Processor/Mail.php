<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessContent;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessData;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessHeaders;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessMailFrom;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail\ProcessRecipients;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

final class Mail implements Processor
{
    /** @var Factory */
    private $replyFactory;

    /** @var Output */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Envelop */
    private $envelop;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Envelop $envelop
    ) {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
        $this->connection   = $connection;
        $this->envelop      = $envelop;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            $processors = [
                new ProcessMailFrom($this->replyFactory, $this->logger, $this->connection, $this->envelop->getMailFromAddress()),
                new ProcessRecipients($this->replyFactory, $this->logger, $this->connection, ...$this->envelop->getRecipients()),
                new ProcessData($this->replyFactory, $this->logger, $this->connection),
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
