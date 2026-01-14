<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Builder;

use JanMarten\IRC\Message\Contract\Component\Source;

final class MessageBuilder
{
    use WithTags;
    use WithSource;

    public function __construct(?Source $source)
    {
        $this->source = $source;
    }

    public function command(string|int $verb, string ...$arguments): CommandMessageBuilder
    {
        return new CommandMessageBuilder(
            verb: $verb,
            arguments: $arguments,
            source: $this->source,
            tags: $this->tagList
        );
    }
}
