<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message;

use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Parser\Command\PCRECommandParser;
use JanMarten\IRC\Message\Parser\Message\PCREMessageParser;
use JanMarten\IRC\Message\Parser\Source\PCRESourceParser;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;

function parse(string $message): Message
{
    static $parser = new PCREMessageParser(
        commandParser: new PCRECommandParser(),
        sourceParser: new PCRESourceParser(),
        tagListParser: new PCRETagListParser(),
    );

    // Ensure CRLF at the end of the line.
    if (! str_ends_with($message, "\r\n")) {
        $message = sprintf(
            "%s\r\n",
            rtrim($message, "\r\n")
        );
    }

    return $parser->parseMessage($message);
}
