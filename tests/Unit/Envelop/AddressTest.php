<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Envelop;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Exception\InvalidEmailAddress;
use HarmonyIO\SmtpClient\Exception\InvalidEmailName;

class AddressTest extends TestCase
{
    public function testConstructorThrowsWhenEmailAddressContainsACarriageReturnCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage("The provided email address (`foo@example.com\r`) is invalid.");

        new Address("foo@example.com\r");
    }

    public function testConstructorThrowsWhenEmailAddressContainsALineFeedCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage("The provided email address (`foo@example.com\n`) is invalid.");

        new Address("foo@example.com\n");
    }

    public function testConstructorThrowsWhenEmailAddressLocalPartContainsASmallerThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`fo<o@example.com`) is invalid.');

        new Address('fo<o@example.com');
    }

    public function testConstructorThrowsWhenEmailAddressLocalPartContainsAGreaterThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`fo>o@example.com`) is invalid.');

        new Address('fo>o@example.com');
    }

    public function testConstructorThrowsWhenEmailAddressDomainContainsASmallerThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`foo@exam<ple.com`) is invalid.');

        new Address('foo@exam<ple.com');
    }

    public function testConstructorThrowsWhenEmailAddressDomainContainsAGreaterThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`foo@exam>ple.com`) is invalid.');

        new Address('foo@exam>ple.com');
    }

    public function testConstructorThrowsWhenEmailAddressTldContainsASmallerThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`foo@example.co<m`) is invalid.');

        new Address('foo@example.co<m');
    }

    public function testConstructorThrowsWhenEmailAddressTldContainsAGreaterThanCharacter(): void
    {
        $this->expectException(InvalidEmailAddress::class);
        $this->expectExceptionMessage('The provided email address (`foo@example.co>m`) is invalid.');

        new Address('foo@example.co>m');
    }

    public function testConstructorThrowsWhenNameContainsACarriageReturnCharacter(): void
    {
        $this->expectException(InvalidEmailName::class);
        $this->expectExceptionMessage("The provided name (`Test\rUser`) contains invalid characters.");

        new Address('foo@example.com', "Test\rUser");
    }

    public function testConstructorThrowsWhenNameContainsALineFeedCharacter(): void
    {
        $this->expectException(InvalidEmailName::class);
        $this->expectExceptionMessage("The provided name (`Test\nUser`) contains invalid characters.");

        new Address('foo@example.com', "Test\nUser");
    }

    public function testGetEmailAddress(): void
    {
        $this->assertSame('foo@example.com', (new Address('foo@example.com'))->getEmailAddress());
    }

    public function testGetNameReturnsNullWhenNotSet(): void
    {
        $this->assertNull((new Address('foo@example.com'))->getName());
    }

    public function testGetNameWhenSet(): void
    {
        $this->assertSame('Test User', (new Address('foo@example.com', 'Test User'))->getName());
    }

    public function testGetRfcAddressWithoutName(): void
    {
        $this->assertSame('<foo@example.com>', (new Address('foo@example.com'))->getRfcAddress());
    }

    public function testGetRfcAddressWithName(): void
    {
        $this->assertSame('<foo@example.com> Test User', (new Address('foo@example.com', 'Test User'))->getRfcAddress());
    }
}
