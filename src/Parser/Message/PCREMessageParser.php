<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Parser\Message;

use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Parser\CommandParser;
use JanMarten\IRC\Message\Contract\Parser\MessageParser;
use JanMarten\IRC\Message\Contract\Parser\SourceParser;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;
use JanMarten\IRC\Message\Exception\MalformedCommandException;
use JanMarten\IRC\Message\Exception\MalformedMessageException;
use JanMarten\IRC\Message\Exception\MalformedSourceException;
use JanMarten\IRC\Message\Exception\MalformedTagListException;
use JanMarten\IRC\Message\Exception\MessageTooLongException;

/**
 * @see https://modern.ircdocs.horse/#message-format
 * @see https://ircv3.net/specs/extensions/message-tags
 */
final readonly class PCREMessageParser implements MessageParser
{
    const int MESSAGE_BYTE_LIMIT = 512;

    const int TAGS_ADDITIONAL_BYTE_LIMIT = 4096;

    const string MESSAGE_EXPRESSION = <<<PCRE
    /^
      # Optional @tags
      (?:(?P<tags>@\S+)\s)?

      # Optional :source
      (?:(?P<source>:\S+)\s)?

      # Command
      (?P<command>[^\r\n]+)

      # Carriage return + linefeed + end of string
      # Match with the absolute end of the string, \Z, not the end of the line, $.
      #
      # This is needed because the CRLF technically introduced multiple lines.
      # We disable the whitespace preservation using ?-x to be explicit about
      #  these whitespace characters.
      (?-x:\r\n\Z)
    /x
    PCRE;

    public function __construct(
        private CommandParser $commandParser,
        private SourceParser $sourceParser,
        private TagListParser $tagListParser
    ) {
    }

    /**
     * @throws MalformedMessageException When the message is not well-formed against the ABNF.
     * @throws MessageTooLongException When the message length exceeds the spec limits.
     */
    public function parseMessage(string $message): Message
    {
        if (!preg_match(self::MESSAGE_EXPRESSION, $message, $matches)) {
            throw new MalformedMessageException(
                'Provided message was not a well-formed IRC message line.'
            );
        }

        $messageLength = strlen($message);
        $tagsLength = strlen($matches['tags']);

        // The message minus the tags cannot exceed a certain length in bytes.
        if (($messageLength - $tagsLength) > self::MESSAGE_BYTE_LIMIT) {
            throw new MessageTooLongException(
                sprintf(
                    'Message too long: %d bytes long, max %d bytes allowed.',
                    $tagsLength,
                    self::TAGS_ADDITIONAL_BYTE_LIMIT
                )
            );
        }

        // When there are tags added to the messages, their length cannot exceed their limit.
        if ($tagsLength > self::TAGS_ADDITIONAL_BYTE_LIMIT) {
            throw new MessageTooLongException(
                sprintf(
                    'Message tags too long: %d bytes long, max %d bytes allowed.',
                    $tagsLength,
                    self::TAGS_ADDITIONAL_BYTE_LIMIT
                )
            );
        }

        try {
            $command = $this->commandParser->parseCommand($matches['command']);
        } catch (MalformedCommandException $parseException) {
            throw new MalformedMessageException(
                message: 'Command is not a well-formed IRC command.',
                previous: $parseException
            );
        }

        $source = null;

        if (strlen($matches['source']) > 0) {
            try {
                $source = $this->sourceParser->parseSource($matches['source']);
            } catch (MalformedSourceException $parseException) {
                throw new MalformedMessageException(
                    message: 'Source is not a well-formed IRC source.',
                    previous: $parseException
                );
            }
        }

        $tags = null;

        if (strlen($matches['tags']) > 0) {
            try {
                $tags = $this->tagListParser->parseTags($matches['tags']);
            } catch (MalformedTagListException $parseException) {
                throw new MalformedMessageException(
                    message: 'Tag list is not a well-formed list of IRC tags.',
                    previous: $parseException
                );
            }
        }

        $tags ??= ImmutableTagList::createNormalizedTagList();

        return new ImmutableMessage($command, $source, $tags);
    }
}
