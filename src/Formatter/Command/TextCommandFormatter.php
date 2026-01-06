<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Command;

use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Formatter\CommandFormatter;

final readonly class TextCommandFormatter implements CommandFormatter
{
    public function formatCommand(Command $command): string
    {
        $result = sprintf('%s', $command->verb);
        $arguments = $command->arguments;

        do {
            $argument = array_shift($arguments);

            if ($argument === null) {
                break;
            }

            if (str_contains($argument, ' ') || str_starts_with($argument, ':')) {
                $result .= implode(
                    ' ',
                    [
                        sprintf(' :%s', $argument),
                        ...$arguments
                    ]
                );
                break;
            }

            $result .= sprintf(' %s', $argument);
        } while (count($arguments) > 0);

        return $result;
    }
}
