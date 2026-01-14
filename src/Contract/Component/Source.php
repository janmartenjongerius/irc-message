<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

interface Source extends MessageComponent
{
    public string $mask { get; }
    public string $nick { get; }
    public ?string $user { get; }
    public ?string $host { get; }
}
