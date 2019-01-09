<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;
use HarmonyIO\SmtpClient\Exception\ConnectionClosedUnexpectedly;
use HarmonyIO\SmtpClient\Log\Output;
use function Amp\call;

final class Buffer
{
    private const LINE_DELIMITER = "\r\n";

    private const LINE_LENGTH_LIMIT = 512;

    /** @var string */
    private $buffer = '';

    /** @var bool */
    private $socketAlive = true;

    /** @var Socket */
    private $socket;

    /** @var Output */
    private $logger;

    public function __construct(SmtpSocket $socket, Output $logger)
    {
        $this->socket = $socket;
        $this->logger = $logger;
    }

    /**
     * @return Promise<string|null>
     */
    public function readLine(): Promise
    {
        return call(function () {
            yield $this->fillBuffer();

            if ($this->buffer === '') {
                return null;
            }

            if (!$this->doesBufferContainLine() && !$this->socketAlive) {
                throw new ConnectionClosedUnexpectedly();
            }

            if (!$this->doesBufferContainLine()) {
                return yield $this->readLine();
            }

            return $this->shiftLineFromBuffer();
        });
    }

    private function doesBufferContainLine(): bool
    {
        return strpos($this->buffer, self::LINE_DELIMITER) !== false;
    }

    /**
     * @return Promise<null>
     */
    private function fillBuffer(): Promise
    {
        return call(function () {
            while ($this->socketAlive && !$this->doesBufferContainLine()) {
                $chunk = yield $this->readChunkFromSocket();

                if ($chunk === null) {
                    break;
                }

                $this->buffer .= $chunk;
            }
        });
    }

    /**
     * @return Promise<string|null>
     */
    private function readChunkFromSocket(): Promise
    {
        return call(function () {
            $chunk = yield $this->socket->read();

            if ($chunk === null) {
                $this->socketAlive = false;

                return null;
            }

            $this->logger->messageIn($chunk);

            return $chunk;
        });
    }

    private function shiftLineFromBuffer(): string
    {
        $lineDelimiterPosition = strpos($this->buffer, self::LINE_DELIMITER);
        $line                  = substr($this->buffer, 0, $lineDelimiterPosition);

        $this->buffer = substr($this->buffer, $lineDelimiterPosition + strlen(self::LINE_DELIMITER));

        if (strlen($line) > self::LINE_LENGTH_LIMIT) {
            $this->logger->info('Line exceeds RFC SMTP line length. We process it anyway,', [
                'line' => $line,
            ]);
        }

        return $line;
    }
}
