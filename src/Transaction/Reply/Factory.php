<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Reply;

use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;

final class Factory
{
    /**
     * @param string[] $allowedReplies
     * @throws UnexpectedReply when the reply from the server does not match the
     *                         list of expected replies for the current transaction status
     */
    public function build(string $line, array $allowedReplies): Reply
    {
        foreach ($allowedReplies as $allowedReply) {
            if (!$allowedReply::isValid($line)) {
                continue;
            }

            return new $allowedReply($line);
        }

        throw new UnexpectedReply($line);
    }
}
