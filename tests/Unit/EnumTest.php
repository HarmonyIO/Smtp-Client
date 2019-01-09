<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Exception\InvalidEnumValue;
use HarmonyIO\SmtpClientTest\Fakes\FakeEnum;

class EnumTest extends TestCase
{
    public function testConstructorThrowsOnInvalidEnumValue(): void
    {
        $this->expectException(InvalidEnumValue::class);
        $this->expectExceptionMessage('`foo` is not a valid `HarmonyIO\SmtpClientTest\Fakes\FakeEnum` value.');

        new FakeEnum('foo');
    }

    public function testConstructorSetsValue(): void
    {
        $this->assertSame(1, (new FakeEnum(FakeEnum::VALID_VALUE))->getValue());
    }
}
