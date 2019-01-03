<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Examples;

use Amp\Loop;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Connection;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(function () {
    $connection = new Connection(
        new ServerAddress('smtp.mailtrap.io', 25),
        new Authentication('foo', 'bar'),
        new Output(Level::ALL())
    );

    $connection->connect();
});
