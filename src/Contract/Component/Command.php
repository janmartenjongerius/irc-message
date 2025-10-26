<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

interface Command
{
    public string|int $verb { get; }

    /** @var array<string> */
    public array $arguments { get; }
}
