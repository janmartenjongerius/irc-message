<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Parser\Command;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Parser\CommandParser;
use JanMarten\IRC\Message\Exception\MalformedCommandException;

/**
 * @see https://modern.ircdocs.horse/#message-format
 */
final readonly class PCRECommandParser implements CommandParser
{
    const string COMMAND_EXPRESSION = <<<PCRE
    /^
      # Command verb or numeric
      (?P<verb>[A-Za-z]+|\d{3})
      
      # Command argument
      (?:\s(?P<arguments>.+))?
    $/x
    PCRE;

    /**
     * @throws MalformedCommandException when the command is not a well-formed IRC command.
     */
    public function parseCommand(string $command): Command
    {
        if (!preg_match(self::COMMAND_EXPRESSION, $command, $matches)) {
            throw new MalformedCommandException(
                'Provided command was not a well-formed IRC command.'
            );
        }

        $argumentStack = explode(' ', $matches['arguments'] ?? '');
        $arguments = [];

        do {
            $argument = array_shift($argumentStack);

            // Ignore empty arguments.
            if ($argument === '') {
                continue;
            }

            // The rest of the list is a single argument.
            if (str_starts_with($argument, ':')) {
                $arguments[] = implode(
                    ' ',
                    [
                        // Strip off the initial :colon character.
                        // Keep any following characters, even if they are a colon.
                        substr($argument, 1),
                        ...$argumentStack
                    ]
                );
                break;
            }

            $arguments[] = $argument;
        } while (count($argumentStack) > 0);

        return new ImmutableCommand($matches['verb'], ...$arguments);
    }
}
