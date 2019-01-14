<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Transaction\Processor;

use Amp\Promise;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Extension\Auth;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn\ProcessCramMd5;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn\ProcessLogin;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn\ProcessPlain;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use function Amp\call;

final class LogIn implements Processor
{
    /** @var Factory */
    private $replyFactory;

    /** @var Output */
    private $logger;

    /** @var Socket */
    private $connection;

    /** @var Collection */
    private $extensions;

    /** @var Authentication|null */
    private $authentication;

    public function __construct(
        Factory $replyFactory,
        Output $logger,
        Socket $connection,
        Collection $extensions,
        ?Authentication $authentication = null
    ) {
        $this->replyFactory   = $replyFactory;
        $this->logger         = $logger;
        $this->connection     = $connection;
        $this->extensions     = $extensions;
        $this->authentication = $authentication;
    }

    public function process(Buffer $buffer): Promise
    {
        return call(function () use ($buffer) {
            if ($this->authentication === null) {
                return;
            }

            if (!$this->extensions->isExtensionEnabled(Auth::class)) {
                return;
            }

            /** @var Auth $authExtension */
            $authExtension = $this->extensions->getExtension(Auth::class);

            yield $this->getAuthenticationProcessor($authExtension)->process($buffer);
        });
    }

    private function getAuthenticationProcessor(Auth $authExtension): Processor
    {
        switch ($authExtension->getPreferredAuthenticationMethod()) {
            case 'PLAIN':
                return new ProcessPlain($this->replyFactory, $this->logger, $this->connection, $this->authentication);

            case 'LOGIN':
                return new ProcessLogin($this->replyFactory, $this->logger, $this->connection, $this->authentication);

            case 'CRAM-MD5':
                return new ProcessCramMd5($this->replyFactory, $this->logger, $this->connection, $this->authentication);
        }
    }
}
