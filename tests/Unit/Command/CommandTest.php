<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Command;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Command\Command;

class CommandTest extends TestCase
{
    public function testToStringProperlyFormatsWithOnlyACommand(): void
    {
        $command = new class extends Command
        {
            public function __construct()
            {
                parent::__construct('COMMANDNAME');
            }
        };

        $this->assertSame("COMMANDNAME\r\n", (string) $command);
    }

    public function testToStringProperlyFormatsWithCommandAndExtraData(): void
    {
        $command = new class extends Command
        {
            public function __construct()
            {
                parent::__construct('COMMANDNAME', 'foo', 'bar');
            }
        };

        $this->assertSame("COMMANDNAME foo bar\r\n", (string) $command);
    }
}
