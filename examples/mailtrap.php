<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Examples;

use Amp\Loop;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Connection;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $connection = new Connection(
        new ServerAddress('smtp.mailtrap.io', 25),
        new Output(Level::SMTP()),
        new Authentication('username', 'password')
    );

    yield $connection->connect(
        new Envelop(new Address('sender@example.com', 'Foo Bar'), new Address('recipient1@example.com', 'Recipient One'))
    );
});
