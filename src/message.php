<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message;

use JanMarten\IRC\Message\Builder\CommandMessageBuilder;
use JanMarten\IRC\Message\Builder\MessageBuilder;
use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Component\Source;

function message(?Source $source = null): MessageBuilder
{
    return new MessageBuilder($source ?? ImmutableSource::fromEnv());
}

function command(Message $message): CommandMessageBuilder
{
    return CommandMessageBuilder::fromMessage($message);
}
