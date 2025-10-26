<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

interface Source
{
    public string $mask { get; }
    public string $nick { get; }
    public ?string $user { get; }
    public ?string $host { get; }
}
