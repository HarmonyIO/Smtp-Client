<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Reply;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use HarmonyIO\SmtpClientTest\Fakes\Transaction\FakeInvalidReply;
use HarmonyIO\SmtpClientTest\Fakes\Transaction\FakeValidReply;

class FactoryTest extends TestCase
{
    public function testBuildThrowsWhenEncounteringAnUnexpectedReply(): void
    {
        $this->expectException(UnexpectedReply::class);
        $this->expectExceptionMessage('Encountered an unexpected server reply (`234 2.3.7 unexpected reply`).');

        (new Factory())->build('234 2.3.7 unexpected reply', []);
    }

    public function testBuildReturnsTheSupportedReply(): void
    {
        $this->assertInstanceOf(FakeValidReply::class, (new Factory())->build('234 2.3.7 expected reply', [
            FakeInvalidReply::class,
            FakeValidReply::class,
        ]));
    }
}
