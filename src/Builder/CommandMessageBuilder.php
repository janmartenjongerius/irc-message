<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Builder;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Component\TagList;
use Stringable;
use function JanMarten\IRC\Message\format;

final class CommandMessageBuilder implements Stringable
{
    use WithTags;
    use WithSource;
    use WithArguments;

    public function __construct(
        public readonly string|int $verb,
        array $arguments,
        ?Source $source,
        TagList $tags,
    ) {
        $this->arguments = $arguments;
        $this->source = $source;
        $this->tags = iterator_to_array($tags);
    }

    public static function fromMessage(Message $message): self
    {
        return new self(
            verb: $message->command->verb,
            arguments: $message->command->arguments,
            source: $message->source,
            tags: $message->tags,
        );
    }

    public function build(): Message
    {
        return new ImmutableMessage(
            command: new ImmutableCommand(
                $this->verb,
                ...array_values($this->arguments)
            ),
            source: $this->source,
            tags: $this->tagList,
        );
    }

    public function __toString(): string
    {
        return format($this->build());
    }
}
