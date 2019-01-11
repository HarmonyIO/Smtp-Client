<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Envelop\Header;

class EnvelopTest extends TestCase
{
    public function testConstructorSetsMessageIdHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $this->assertArrayHasKey('message-id', $envelop->getHeaders());
        $this->assertSame('Message-ID', $envelop->getHeaders()['message-id']->getKey());
        $this->assertRegExp('~^<.{32}@\[127.0.0.1\]>$~', $envelop->getHeaders()['message-id']->getValue());
    }

    public function testConstructorSetsDateHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $this->assertArrayHasKey('date', $envelop->getHeaders());
        $this->assertSame('Date', $envelop->getHeaders()['date']->getKey());
        $this->assertRegExp(
            '~^[A-Z][a-z]{2}, \d{1,2} [A-Z][a-z]{2} \d{1,2} \d{1,2}:\d{1,2}:\d{1,2} (\+|-)\d{4}$~',
            $envelop->getHeaders()['date']->getValue()
        );
    }

    public function testConstructorSetsFromHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $this->assertArrayHasKey('from', $envelop->getHeaders());
        $this->assertSame('From', $envelop->getHeaders()['from']->getKey());
        $this->assertSame('<from@example.com>', $envelop->getHeaders()['from']->getValue());
    }

    public function testConstructorSetsToHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $this->assertArrayHasKey('to', $envelop->getHeaders());
        $this->assertSame('To', $envelop->getHeaders()['to']->getKey());
        $this->assertSame('<to@example.com>', $envelop->getHeaders()['to']->getValue());
    }

    public function testAddHeaderAddsHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $envelop->addHeader(new Header('TheKey', 'TheValue'));

        $this->assertArrayHasKey('thekey', $envelop->getHeaders());
        $this->assertSame('TheKey', $envelop->getHeaders()['thekey']->getKey());
        $this->assertSame('TheValue', $envelop->getHeaders()['thekey']->getValue());
    }

    public function testReplyToAddressAddsHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $envelop->replyToAddress(new Address('replyto@example.com', 'Reply To'));

        $this->assertArrayHasKey('reply-to', $envelop->getHeaders());
        $this->assertSame('Reply-To', $envelop->getHeaders()['reply-to']->getKey());
        $this->assertSame('<replyto@example.com> Reply To', $envelop->getHeaders()['reply-to']->getValue());
    }

    public function testSubjectAddsHeader(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $envelop->subject('TheSubject');

        $this->assertArrayHasKey('subject', $envelop->getHeaders());
        $this->assertSame('Subject', $envelop->getHeaders()['subject']->getKey());
        $this->assertSame('TheSubject', $envelop->getHeaders()['subject']->getValue());
    }

    public function testBodySetsBody(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $envelop->body('TheBody');

        $this->assertSame('TheBody', $envelop->getBody());
    }

    public function testGetMailFromAddress(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $this->assertInstanceOf(Address::class, $envelop->getMailFromAddress());
        $this->assertSame('from@example.com', $envelop->getMailFromAddress()->getEmailAddress());
    }

    public function testGetRecipients(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to1@example.com'),
            new Address('to2@example.com')
        );

        $this->assertCount(2, $envelop->getRecipients());
        $this->assertSame('to1@example.com', $envelop->getRecipients()[0]->getEmailAddress());
        $this->assertSame('to2@example.com', $envelop->getRecipients()[1]->getEmailAddress());
    }

    public function testGetHeaders(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to1@example.com'),
            new Address('to2@example.com')
        );

        $this->assertCount(4, $envelop->getHeaders());
    }

    public function testGetBody(): void
    {
        $envelop = new Envelop(
            new Localhost(),
            new Address('from@example.com'),
            new Address('to@example.com')
        );

        $envelop->body('TheBody');

        $this->assertSame('TheBody', $envelop->getBody());
    }
}
