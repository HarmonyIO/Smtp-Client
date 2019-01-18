<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Log;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Log\Builder;
use HarmonyIO\SmtpClient\Log\Logger;

class BuilderTest extends TestCase
{
    public function testBuildConsoleLogger(): void
    {
        $this->assertInstanceOf(Logger::class, Builder::buildConsoleLogger());
    }
}
