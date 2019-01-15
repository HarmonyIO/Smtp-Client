<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception\Smtp;

use HarmonyIO\SmtpClient\Exception\Smtp;

class TransactionFailed extends Smtp
{
    public function __construct(?string $reason = null)
    {
        if ($reason === null) {
            parent::__construct(sprintf('Transaction failed.'));

            return;
        }

        parent::__construct(sprintf('Transaction failed with reason: `%s`.', $reason));
    }
}
