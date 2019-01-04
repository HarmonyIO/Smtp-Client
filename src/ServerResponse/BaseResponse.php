<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\ServerResponse;

abstract class BaseResponse implements Response
{
    /** @var bool */
    private $isLastResponse = false;

    public function __construct(string $line)
    {
        if (preg_match('~^\d{3}($| )~', $line) !== 1) {
            return;
        }

        $this->isLastResponse = true;
    }

    public function isLastResponse(): bool
    {
        return $this->isLastResponse;
    }
}
