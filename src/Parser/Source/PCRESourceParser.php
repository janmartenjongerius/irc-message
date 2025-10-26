<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Parser\Source;

use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Parser\SourceParser;
use JanMarten\IRC\Message\Exception\MalformedSourceException;

/**
 * @see https://modern.ircdocs.horse/#message-format
 */
final readonly class PCRESourceParser implements SourceParser
{
    const string SOURCE_EXPRESSION = <<<PCRE
    /^:
      # Nick or server
      (?P<nick>[^!@ ]+)
      
      # Optional !user
      (?:!(?P<user>[^@ ]+))?
      
      # Optional @host
      (?:@(?P<host>[^ ]+))?
    $/x
    PCRE;

    /**
     * @throws MalformedSourceException When the source is not well-formed.
     */
    public function parseSource(string $source): Source
    {
        if (!preg_match(self::SOURCE_EXPRESSION, $source, $matches)) {
            throw new MalformedSourceException(
                'Provided source was not a well-formed IRC source.'
            );
        }

        return new ImmutableSource(
            nick: $matches['nick'],
            user: strlen($matches['user'] ?? '') > 0 ? $matches['user'] : null,
            host: strlen($matches['host'] ?? '') > 0 ? $matches['host'] : null,
        );
    }
}
