<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Examples;

use Amp\Loop;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Connection;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\ServerAddress;
use HarmonyIO\SmtpClient\Transaction;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\Factory as ExtensionFactory;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory as ReplyFactory;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $logger = new Output(new Level(Level::ALL));

    $connection = yield (new Connection(
        new ServerAddress('smtp.mailtrap.io', 25),
        $logger
    ))->connect();

    $authentication = new Authentication('username', 'password');
    $replyFactory   = new ReplyFactory();
    $extensions     = new Collection(new ExtensionFactory());

    $transaction = new Transaction(new Buffer($connection, $logger));

    yield $transaction->run(
        new Handshake($replyFactory, $logger),
        new ExtensionNegotiation($replyFactory, $logger, $connection, new Localhost(), $extensions),
        new LogIn($replyFactory, $logger, $connection, $extensions, $authentication)
    );
});
