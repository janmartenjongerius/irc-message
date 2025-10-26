<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Parser;

use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Exception\MalformedMessageException;
use JanMarten\IRC\Message\Exception\MessageTooLongException;

interface MessageParser
{
    /**
     * @throws MalformedMessageException When the message is not well-formed against the ABNF.
     * @throws MessageTooLongException When the message length exceeds the spec limits.
     */
    public function parseMessage(string $message): Message;
}
