<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Log;

use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Log\ConsoleFormatter;
use Psr\Log\LogLevel;

class ConsoleFormatterTest extends TestCase
{
    /** @var string */
    private $ampLogColorSetting;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->ampLogColorSetting = getenv('AMP_LOG_COLOR');
    }

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function tearDown()
    {
        getenv('AMP_LOG_COLOR=' . $this->ampLogColorSetting);
    }

    public function testFormatReplacesNonPrintableCharacters(): void
    {
        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "foo\r\n\0\tbar",
            'level_name' => LogLevel::INFO,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame('%datetime% [foobar] info: foo\r\n\0\tbar  ' . PHP_EOL, $formatter->format($record));
    }

    public function testFormatWithoutColorSupport(): void
    {
        putenv('AMP_LOG_COLOR=off');

        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "The log message",
            'level_name' => LogLevel::INFO,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame('%datetime% [foobar] info: The log message  ' . PHP_EOL, $formatter->format($record));
    }

    public function testFormatInfoWithColorSupport(): void
    {
        putenv('AMP_LOG_COLOR=on');

        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "The log message",
            'level_name' => LogLevel::INFO,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame("%datetime% [\033[1mfoobar\033[0m] \033[1;35minfo\033[0m: The log message  " . PHP_EOL, $formatter->format($record));
    }

    public function testFormatWarningWithColorSupport(): void
    {
        putenv('AMP_LOG_COLOR=on');

        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "The log message",
            'level_name' => LogLevel::WARNING,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame("%datetime% [\033[1mfoobar\033[0m] \033[1;33mwarning\033[0m: The log message  " . PHP_EOL, $formatter->format($record));
    }

    public function testFormatNoticeWithColorSupport(): void
    {
        putenv('AMP_LOG_COLOR=on');

        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "The log message",
            'level_name' => LogLevel::NOTICE,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame("%datetime% [\033[1mfoobar\033[0m] \033[1;32mnotice\033[0m: The log message  " . PHP_EOL, $formatter->format($record));
    }

    public function testFormatDebugWithColorSupport(): void
    {
        putenv('AMP_LOG_COLOR=on');

        $formatter = new ConsoleFormatter();

        $record = [
            'message'    => "The log message",
            'level_name' => LogLevel::DEBUG,
            'channel'    => 'foobar',
            'extra'      => [],
            'context'    => [],
        ];

        $this->assertSame("%datetime% [\033[1mfoobar\033[0m] \033[1;36mdebug\033[0m: The log message  " . PHP_EOL, $formatter->format($record));
    }
}
