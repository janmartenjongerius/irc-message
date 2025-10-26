<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Command;

final readonly class ImmutableCommand implements Command
{
    /** @var array<string> */
    public array $arguments;

    public function __construct(
        public string|int $verb,
        string ...$arguments
    ) {
        $this->arguments = $arguments;
    }
}
