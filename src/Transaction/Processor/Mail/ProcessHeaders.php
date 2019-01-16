<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor\Mail;

use Amp\Promise;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\Socket;
use HarmonyIO\SmtpClient\Envelop\Header;
use HarmonyIO\SmtpClient\Transaction\Command\Header as HeaderCommand;
use HarmonyIO\SmtpClient\Transaction\Command\HeadersAndBodySeparator;
use HarmonyIO\SmtpClient\Transaction\Processor\Processor;
use HarmonyIO\SmtpClient\Transaction\Status\Mail as Status;
use function Amp\call;

class ProcessHeaders implements Processor
{
    /** @var Socket */
    private $connection;

    /** @var Header[] */
    private $headers;

    public function __construct(Socket $connection, Header ...$headers)
    {
        $this->connection = $connection;
        $this->headers    = $headers;
    }

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
    public function process(Buffer $buffer): Promise
    {
        return call(function () {
            yield $this->processHeaders();

            return new Status(Status::SENT_HEADERS);
        });
    }

    private function processHeaders(): Promise
    {
        foreach ($this->headers as $header) {
            $this->connection->write(new HeaderCommand($header));
        }

        return $this->connection->write(new HeadersAndBodySeparator());
    }
}
