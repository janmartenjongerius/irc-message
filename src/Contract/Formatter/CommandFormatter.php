<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Formatter;

use JanMarten\IRC\Message\Contract\Component\Command;

interface CommandFormatter
{
    public function formatCommand(Command $command): string;
}
