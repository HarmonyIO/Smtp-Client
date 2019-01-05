<?php declare(strict_types=1);

namespace HarmonyIO\SmtpClient\Exception;

class SpaceCharacter extends Exception
{
    public function __construct()
    {
        parent::__construct('Invalid `space` character encountered.');
    }
}
