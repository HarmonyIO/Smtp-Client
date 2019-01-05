<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Exception;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\SpaceCharacter;

class SpaceCharacterTest extends TestCase
{
    public function testExceptionReturnsCorrectMessage(): void
    {
        $this->expectExceptionMessage('Invalid `space` character encountered.');

        throw new SpaceCharacter();
    }
}
