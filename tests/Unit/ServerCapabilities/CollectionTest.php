<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\ServerCapabilities;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\ServerCapabilities\Collection;

class CollectionTest extends TestCase
{
    public function testCapabilityGetsProperlyAdded(): void
    {
        $collection = new Collection();

        $collection->addCapability(new \DateTimeImmutable());

        $this->assertTrue($collection->isCapableOf(\DateTimeImmutable::class));
    }

    public function testIsCapableOfWhenObjectHasNotBeenAdded(): void
    {
        $this->assertFalse((new Collection())->isCapableOf(\DateTimeImmutable::class));
    }

    public function testIsCapableOfWhenObjectHasBeenAdded(): void
    {
        $collection = new Collection();

        $collection->addCapability(new \DateTimeImmutable());

        $this->assertTrue($collection->isCapableOf(\DateTimeImmutable::class));
    }

    public function testGetCapabilityWhenObjectHasNotBeenAdded(): void
    {
        $this->assertNull((new Collection())->getCapability(\DateTimeImmutable::class));
    }

    public function testGetCapabilityWhenObjectHasBeenAdded(): void
    {
        $collection = new Collection();

        $object = new \DateTimeImmutable();

        $collection->addCapability($object);

        $this->assertSame($object, $collection->getCapability(\DateTimeImmutable::class));
    }
}
