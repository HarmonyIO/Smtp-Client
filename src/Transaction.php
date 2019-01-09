<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use function Amp\call;

final class Transaction
{
    /** @var Buffer */
    private $buffer;

    public function __construct(Buffer $buffer)
    {
        $this->buffer = $buffer;
    }

    /**
     * @return Promise<null>
     */
    public function run(Processor ...$transactionProcessors): Promise
    {
        return call(function () use ($transactionProcessors) {
            foreach ($transactionProcessors as $transactionProcessor) {
                yield $transactionProcessor->process($this->buffer);
            }
        });
    }
}
