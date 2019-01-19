<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Examples;

use Amp\Loop;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\ClientAddress\Localhost;
use HarmonyIO\SmtpClient\Connection\Buffer;
use HarmonyIO\SmtpClient\Connection\PlainConnection;
use HarmonyIO\SmtpClient\Envelop;
use HarmonyIO\SmtpClient\Envelop\Address;
use HarmonyIO\SmtpClient\Log\Builder as LogBuilder;
use HarmonyIO\SmtpClient\ServerAddress;
use HarmonyIO\SmtpClient\Transaction;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Extension\Factory as ExtensionFactory;
use HarmonyIO\SmtpClient\Transaction\Processor\ExtensionNegotiation;
use HarmonyIO\SmtpClient\Transaction\Processor\Handshake;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn;
use HarmonyIO\SmtpClient\Transaction\Processor\Mail;
use HarmonyIO\SmtpClient\Transaction\Processor\MailPipelining;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory as ReplyFactory;

require_once __DIR__ . '/../vendor/autoload.php';

Loop::run(static function () {
    $logger = LogBuilder::buildConsoleLogger();

    $connection = yield (new PlainConnection(
        new ServerAddress('smtp.mailtrap.io', 2525),
        $logger
    ))->connect();

    $clientAddress  = new Localhost();
    $authentication = new Authentication('username', 'password');
    $replyFactory   = new ReplyFactory();
    $extensions     = new Collection(new ExtensionFactory());
    $envelop        = (new Envelop(
        $clientAddress,
        new Address('sender@example.com', 'Example Sender'),
        new Address('receiver1@example.com', 'Example Receiver1'),
        new Address('receiver2@example.com', 'Example Receiver2')
    ))
        ->replyToAddress(new Address('reply-to@example.com', 'Reply Address'))
        ->subject('Fisher-Price my first mail')
        ->body('This is the body')
    ;

    $transaction = new Transaction(new Buffer($connection, $logger));

    yield $transaction->run(
        new Handshake($replyFactory, $logger),
        new ExtensionNegotiation($replyFactory, $logger, $connection, $clientAddress, $extensions),
        new LogIn($replyFactory, $logger, $connection, $extensions, $authentication),
        new Mail($replyFactory, $logger, $connection, $envelop, $extensions),
        new MailPipelining($replyFactory, $logger, $connection, $envelop, $extensions),
    );
});
