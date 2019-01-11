<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Exception\NulByte;
use HarmonyIO\SmtpClient\Exception\SpaceCharacter;

class AuthenticationTest extends TestCase
{
    public function testConstructorThrowsWhenUsernameContainsANulByte(): void
    {
        $this->expectException(NulByte::class);

        new Authentication("The\0Username", 'ThePassword');
    }

    public function testConstructorThrowsWhenUsernameContainsASpaceCharacter(): void
    {
        $this->expectException(SpaceCharacter::class);

        new Authentication('The Username', 'ThePassword');
    }

    public function testConstructorThrowsWhenPasswordContainsANulByte(): void
    {
        $this->expectException(NulByte::class);

        new Authentication('TheUsername', "The\0Password");
    }

    public function testGetUsername(): void
    {
        $this->assertSame('TheUsername', (new Authentication('TheUsername', 'ThePassword'))->getUsername());
    }

    public function testGetPassword(): void
    {
        $this->assertSame('ThePassword', (new Authentication('TheUsername', 'ThePassword'))->getPassword());
    }
}
