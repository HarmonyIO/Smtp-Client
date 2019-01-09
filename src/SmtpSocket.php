<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient;

use Amp\Promise;

interface SmtpSocket
{
    /**
     * @return Promise<null|string>
     */
    public function read(): Promise;

    /**
     * @return Promise<null>
     */
    public function write(string $data): Promise;

    /**
     * @return Promise<null>
     */
    public function end(string $data = ''): Promise;
}
