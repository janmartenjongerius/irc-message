<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Command;

use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Formatter\CommandFormatter;

final readonly class TextCommandFormatter implements CommandFormatter
{
    public function formatCommand(Command $command): string
    {
        return implode(' ', [$command->verb, ...$command->arguments]);
    }
}
