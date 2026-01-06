<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Formatter\Message;

use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Formatter\CommandFormatter;
use JanMarten\IRC\Message\Contract\Formatter\MessageFormatter;
use JanMarten\IRC\Message\Contract\Formatter\SourceFormatter;
use JanMarten\IRC\Message\Contract\Formatter\TagListFormatter;

final readonly class TextMessageFormatter implements MessageFormatter
{
    public function __construct(
        private CommandFormatter $commandFormatter,
        private SourceFormatter $sourceFormatter,
        private TagListFormatter $tagListFormatter
    ) {
    }

    public function formatMessage(Message $message): string
    {
        $result = [];

        if ($message->tags->count() > 0) {
            $result[] = $this->tagListFormatter->formatTagList($message->tags);
        }

        if ($message->source !== null) {
            $result[] = $this->sourceFormatter->formatSource($message->source);
        }

        $result[] = $this->commandFormatter->formatCommand($message->command);

        return implode(' ', $result) . "\r\n";
    }
}
