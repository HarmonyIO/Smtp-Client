<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Log;

use Amp\ByteStream\ResourceOutputStream;
use Amp\Log\StreamHandler;
use Monolog\Logger as MonoLogger;

class Builder
{
    private function __construct()
    {
    }

    public static function buildConsoleLogger(): Logger
    {
        $logHandler = new StreamHandler(new ResourceOutputStream(STDOUT));

        $logHandler->setFormatter(new ConsoleFormatter());

        return new Logger(
            new MonoLogger('SMTP_IN', [$logHandler]),
            new MonoLogger('SMTP_OUT', [$logHandler]),
            new MonoLogger('GENERAL', [$logHandler])
        );
    }
}
