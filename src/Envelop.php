<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\Envelop\Address;

class Envelop
{
    /** @var Address */
    private $mailFromAddress;

    /** @var Address[] */
    private $recipients;

    public function __construct(Address $mailFromAddress, Address $recipient, Address ...$recipients)
    {
        $this->mailFromAddress = $mailFromAddress;
        $this->recipients      = array_merge([$recipient], $recipients);
    }

    public function getMailFromAddress(): Address
    {
        return $this->mailFromAddress;
    }

    /**
     * @return Address[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }
}
