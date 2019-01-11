<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClientTest\Unit\Transaction\Processor;

use Amp\Socket\ServerSocket;
use HarmonyIO\PHPUnitExtension\TestCase;
use HarmonyIO\SmtpClient\Authentication;
use HarmonyIO\SmtpClient\Buffer;
use HarmonyIO\SmtpClient\Log\Level;
use HarmonyIO\SmtpClient\Log\Output;
use HarmonyIO\SmtpClient\SmtpSocket;
use HarmonyIO\SmtpClient\Socket;
use HarmonyIO\SmtpClient\Transaction\Extension\Builder;
use HarmonyIO\SmtpClient\Transaction\Extension\Collection;
use HarmonyIO\SmtpClient\Transaction\Processor\LogIn;
use HarmonyIO\SmtpClient\Transaction\Reply\Factory;
use PHPUnit\Framework\MockObject\MockObject;
use function Amp\Promise\wait;

class LogInTest extends TestCase
{
    /** @var Output */
    private $logger;

    /** @var ServerSocket|MockObject $socket */
    private $socket;

    /** @var Builder|MockObject $extensionFactory */
    private $extensionFactory;

    /** @var SmtpSocket|MockObject $smtpSocket */
    private $smtpSocket;

    /** @var Collection */
    private $extensions;

    /** @var LogIn */
    private $unauthenticatedProcessor;

    /** @var LogIn */
    private $authenticatedProcessor;

    // phpcs:ignore SlevomatCodingStandard.TypeHints.TypeHintDeclaration.MissingReturnTypeHint
    public function setUp()
    {
        $this->logger           = new Output(new Level(Level::NONE));
        $this->socket           = $this->createMock(ServerSocket::class);
        $this->extensionFactory = $this->createMock(Builder::class);
        $this->smtpSocket       = $this->createMock(SmtpSocket::class);
        $this->extensions       = new Collection($this->extensionFactory);

        $this->unauthenticatedProcessor = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $this->extensions
        );

        $this->authenticatedProcessor = new LogIn(
            new Factory(),
            $this->logger,
            new Socket($this->logger, $this->socket),
            $this->extensions,
            new Authentication('TheUsername', 'ThePassword')
        );
    }

    public function testProcessBailsOutWhenUnauthenticated(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->unauthenticatedProcessor->process($buffer));
    }

    public function testProcessBailsOutWhenTheAuthExtensionIsNotEnabled(): void
    {
        $this->smtpSocket
            ->expects($this->never())
            ->method('read')
        ;

        $buffer = new Buffer($this->smtpSocket, $this->logger);

        wait($this->authenticatedProcessor->process($buffer));
    }
}
