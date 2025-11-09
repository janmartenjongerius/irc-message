<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

interface Tag
{
    public string $key { get; }
    public string|true $value { get; }
    public bool $clientOnly { get; }

    public ?string $vendor { get; }
}
