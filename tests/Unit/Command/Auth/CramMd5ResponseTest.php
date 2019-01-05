<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command\Auth;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Command\Auth\CramMd5Response;
use HarmonyIO\SmtpClient\ServerResponse\StartedCramMd5Auth\Challenge;

class CramMd5ResponseTest extends TestCase
{
    public function testToStringAddsUsername(): void
    {
        $command = (string) (new CramMd5Response(
            new Authentication('foo', 'bar'),
            new Challenge('334 dGVzdGNoYWxsZW5nZQ==')
        ));

        $data = explode(' ', base64_decode($command));

        $this->assertCount(2, $data);
        $this->assertSame('foo', $data[0]);
    }

    public function testToStringAddsSignature(): void
    {
        $command = (string) (new CramMd5Response(
            new Authentication('foo', 'bar'),
            new Challenge('334 dGVzdGNoYWxsZW5nZQ==')
        ));

        $data = explode(' ', base64_decode($command));

        $this->assertCount(2, $data);
        $this->assertSame(hash_hmac('md5', 'testchallenge', 'bar'), $data[1]);
    }
}
