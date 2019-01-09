<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception\Smtp;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\Smtp\UnexpectedReply;

class UnexpectedReplyTest extends TestCase
{
    public function testExceptionContainsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Encountered an unexpected server reply (`300 I dun goofed`).');

        throw new UnexpectedReply('300 I dun goofed');
    }
}
