<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Parser;

use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Exception\MalformedCommandException;

interface CommandParser
{
    /**
     * @throws MalformedCommandException when the command is not a well-formed IRC command.
     */
    public function parseCommand(string $command): Command;
}
