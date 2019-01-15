<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

use HarmonyIO\SmtpClient\Exception\Smtp\InvalidReply;

abstract class Reply
{
    private const REPLY_PATTERN = '~^(?P<code>\d{3})(?:(?P<multiLine>-| )(?P<text>.+))?$~';

    private const TEXT_PATTERN = '~^(?:(?P<extendedStatusCode>\d+\.\d+\.\d+) ?)?(?P<text>.*)$~';

    /** @var string */
    private $reply;

    /** @var int */
    private $code;

    /** @var bool */
    private $lastLine;

    /** @var string|null */
    private $extendedStatusCode = null;

    /** @var string|null */
    private $text = null;

    public function __construct(string $line)
    {
        $this->reply = $line;

        if (preg_match(self::REPLY_PATTERN, $line, $matches) !== 1) {
            throw new InvalidReply($line);
        }

        $this->code     = (int) $matches['code'];
        $this->lastLine = !isset($matches['multiLine']) || $matches['multiLine'] === ' ';

        if (!isset($matches['text'])) {
            return;
        }

        $this->parseReplyText($matches['text']);
    }

    private function parseReplyText(string $text): void
    {
        preg_match(self::TEXT_PATTERN, $text, $matches);

        if (isset($matches['extendedStatusCode']) && $matches['extendedStatusCode']) {
            $this->extendedStatusCode = $matches['extendedStatusCode'];
        }

        if (!isset($matches['text']) || !$matches['text']) {
            return;
        }

        $this->text = $matches['text'];
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function isLastLine(): bool
    {
        return $this->lastLine;
    }

    public function getExtendedStatusCode(): ?string
    {
        return $this->extendedStatusCode;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function __toString(): string
    {
        return $this->reply;
    }
}
