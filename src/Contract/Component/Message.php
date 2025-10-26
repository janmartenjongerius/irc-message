<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

interface Message
{
    public Command $command { get; }
    public ?Source $source { get; }
    public ?TagList $tags { get; }
}
