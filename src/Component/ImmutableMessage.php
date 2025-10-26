<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Component\TagList;

final readonly class ImmutableMessage implements Message
{
    public function __construct(
        public Command $command,
        public ?Source $source,
        public ?TagList $tags
    ) {
    }
}
