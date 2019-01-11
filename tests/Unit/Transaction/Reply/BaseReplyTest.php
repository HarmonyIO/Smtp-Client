<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\InvalidReply;
use HarmonyIO\SmtpClientTest\Fakes\Transaction\FakeReply;

class BaseReplyTest extends TestCase
{
    public function testConstructorThrowsWhenReplyDoesNotContainAThreeDigitStatusCode(): void
    {
        $this->expectException(InvalidReply::class);
        $this->expectExceptionMessage('d20 test message');

        new FakeReply('d20 test message');
    }

    public function testGetCode(): void
    {
        $this->assertSame(234, (new FakeReply('234 test message'))->getCode());
    }

    public function testGetCodeWithText(): void
    {
        $this->assertSame(234, (new FakeReply('234'))->getCode());
    }

    public function testIsLastLineReturnsFalseWhenNotLastLine(): void
    {
        $this->assertFalse((new FakeReply('234-test message'))->isLastLine());
    }

    public function testIsLastLineReturnsTrueWhenLastLine(): void
    {
        $this->assertTrue((new FakeReply('234 test message'))->isLastLine());
    }

    public function testGetExtendedStatusCodeReturnsNullWhenStatusCodeIsNotAvailable(): void
    {
        $this->assertNull((new FakeReply('234 test message'))->getExtendedStatusCode());
    }

    public function testGetExtendedStatusCodeReturnsExtendedStatusCodeWhenAvailable(): void
    {
        $this->assertSame('2.3.17', (new FakeReply('234 2.3.17 test message'))->getExtendedStatusCode());
    }

    public function testGetTextReturnsNullWhenNoTextIsProvided(): void
    {
        $this->assertNull((new FakeReply('234 2.3.17'))->getText());
    }

    public function testGetTextReturnsTextWhenAvailable(): void
    {
        $this->assertSame('test message', (new FakeReply('234 2.3.17 test message'))->getText());
    }

    public function testToStringReturnsEntireLine(): void
    {
        $this->assertSame('234 2.3.17 test message', (string) new FakeReply('234 2.3.17 test message'));
    }
}
