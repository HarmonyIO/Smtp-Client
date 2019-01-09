<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

interface Reply
{
    public static function isValid(string $line): bool;

    public function getCode(): int;

    public function isLastLine(): bool;

    public function getExtendedStatusCode(): ?string;

    public function getText(): ?string;

    public function __toString(): string;
}
