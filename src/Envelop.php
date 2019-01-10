<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use HarmonyIO\SmtpClient\ClientAddress\Address as ClientAddress;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Envelop\Header;

class Envelop
{
    /** @var Address */
    private $mailFromAddress;

    /** @var Address[] */
    private $recipients;

    /** @var Header[] */
    private $headers = [];

    /** @var string */
    private $body;

    public function __construct(
        ClientAddress $clientAddress,
        Address $mailFromAddress,
        Address $recipient,
        Address ...$recipients
    ) {
        $this->mailFromAddress = $mailFromAddress;
        $this->recipients      = array_merge([$recipient], $recipients);

        $this->addHeader(new Header('Message-ID', sprintf('<%s@%s>', bin2hex(random_bytes(16)), $clientAddress->getAddress())));
        $this->addHeader(new Header('Date', (new \DateTimeImmutable())->format(\DateTimeInterface::RFC822)));
        $this->addHeader(new Header('From', $mailFromAddress->getRfcAddress()));
        $this->addHeader(new Header('To', implode(', ', array_reduce(array_merge([$recipient], $recipients), static function (array $recipients, Address $address) {
            $recipients[] = $address->getRfcAddress();

            return $recipients;
        }, []))));
    }

    public function addHeader(Header $header): self
    {
        $this->headers[strtolower($header->getKey())] = $header;

        return $this;
    }

    public function replyToAddress(Address $address): self
    {
        return $this->addHeader(new Header('Reply-To', $address->getRfcAddress()));
    }

    public function subject(string $subject): self
    {
        return $this->addHeader(new Header('Subject', $subject));
    }

    public function body(string $text): self
    {
        $this->body = $text;

        return $this;
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

    /**
     * @return Header[]
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getBody(): string
    {
        return (string) $this->body;
    }
}
