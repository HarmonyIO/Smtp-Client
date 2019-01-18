<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Log\Logger;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake\ProcessBanner;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake\ProcessGreeting;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClient\Transaction\Status\Handshake as Status;
use function Amp\call;

final class Handshake implements Processor
{
    /** @var Factory */
    private $replyFactory;

    /** @var Logger */
    private $logger;

    public function __construct(Factory $replyFactory, Logger $logger)
    {
        $this->replyFactory = $replyFactory;
        $this->logger       = $logger;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            $processors = [
                new ProcessGreeting($this->replyFactory, $this->logger),
                new ProcessBanner($this->replyFactory, $this->logger),
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
