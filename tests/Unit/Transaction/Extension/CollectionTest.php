<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Extension;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Reply\Reply;
use PHPUnit\Framework\MockObject\MockObject;

class CollectionTest extends TestCase
{
    /** @var Builder|MockObject */
    private $factory;

    /** @var Reply|MockObject */
    private $reply;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->factory = $this->createMock(Builder::class);
        $this->reply   = $this->createMock(Reply::class);
    }

    public function testEnableAddsExtensionWhenSupported(): void
    {
        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn(new \stdClass())
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertTrue($collection->isExtensionEnabled(\stdClass::class));
    }

    public function testEnableIgnoresExtensionWhenNotSupported(): void
    {
        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn(null)
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertFalse($collection->isExtensionEnabled(\stdClass::class));
    }

    public function testIsExtensionEnabledReturnsTrueWhenAdded(): void
    {
        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn(new \stdClass())
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertTrue($collection->isExtensionEnabled(\stdClass::class));
    }

    public function testIsExtensionEnabledReturnsFalseWhenNotAdded(): void
    {
        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn(null)
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertFalse($collection->isExtensionEnabled(\stdClass::class));
    }

    public function testGetExtensionEnabledReturnsNullWhenNotAdded(): void
    {
        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn(null)
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertNull($collection->getExtension(\stdClass::class));
    }

    public function testGetExtensionEnabledReturnsExtensionWhenAdded(): void
    {
        $extension = new \stdClass();

        $this->factory
            ->expects($this->once())
            ->method('build')
            ->willReturn($extension)
        ;

        $this->reply
            ->method('getText')
            ->willReturn('EXTENSION')
        ;

        $collection = new Collection($this->factory);

        $collection->enable($this->reply);

        $this->assertSame($extension, $collection->getExtension(\stdClass::class));
    }
}
