<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message;

use InvalidArgumentException;
use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Component\MessageComponent;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use JanMarten\IRC\Message\Formatter\Message\TextMessageFormatter;
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;

function format(Message|MessageComponent $ircMessageComponent): string
{
    static $commandFormatter = new TextCommandFormatter();
    static $sourceFormatter = new TextSourceFormatter();
    static $tagFormatter = new TextTagFormatter();
    static $messageFormatter = new TextMessageFormatter(
        commandFormatter: $commandFormatter,
        sourceFormatter: $sourceFormatter,
        tagListFormatter: $tagFormatter,
    );

    if ($ircMessageComponent instanceof Message) {
        return $messageFormatter->formatMessage($ircMessageComponent);
    }

    if ($ircMessageComponent instanceof Command) {
        return $commandFormatter->formatCommand($ircMessageComponent);
    }

    if ($ircMessageComponent instanceof Source) {
        return $sourceFormatter->formatSource($ircMessageComponent);
    }

    if ($ircMessageComponent instanceof Tag) {
        return $tagFormatter->formatTag($ircMessageComponent);
    }

    if ($ircMessageComponent instanceof TagList) {
        return $tagFormatter->formatTagList($ircMessageComponent);
    }

    throw new InvalidArgumentException(
        sprintf(
            'No supported formatter for component %s',
            print_r($ircMessageComponent, true)
        )
    );
}
