<?php
declare(strict_types=1);

namespace Integration\Parser\Message;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Parser\MessageParser;
use JanMarten\IRC\Message\Exception\InvalidMessageException;
use JanMarten\IRC\Message\Exception\MalformedCommandException;
use JanMarten\IRC\Message\Exception\MalformedMessageException;
use JanMarten\IRC\Message\Exception\MalformedSourceException;
use JanMarten\IRC\Message\Exception\MalformedTagListException;
use JanMarten\IRC\Message\Exception\MessageTooLongException;
use JanMarten\IRC\Message\Exception\ParseException;
use JanMarten\IRC\Message\Parser\Command\PCRECommandParser;
use JanMarten\IRC\Message\Parser\Message\PCREMessageParser;
use JanMarten\IRC\Message\Parser\Source\PCRESourceParser;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PCREMessageParser::class)]
#[CoversClass(ImmutableMessage::class)]
#[CoversClass(MessageTooLongException::class)]
#[CoversClass(MalformedMessageException::class)]
#[CoversClass(InvalidMessageException::class)]
#[CoversClass(ParseException::class)]
#[UsesClass(PCRETagListParser::class)]
#[UsesClass(PCRECommandParser::class)]
#[UsesClass(PCRESourceParser::class)]
#[UsesClass(ImmutableCommand::class)]
#[UsesClass(ImmutableSource::class)]
#[UsesClass(ImmutableTag::class)]
#[UsesClass(ImmutableTagList::class)]
class PCREMessageParserTest extends TestCase
{
    public static function createParser(): MessageParser
    {
        return new PCREMessageParser(
            commandParser: new PCRECommandParser(),
            sourceParser: new PCRESourceParser(),
            tagListParser: new PCRETagListParser(),
        );
    }

    #[DataProvider('provideTestParseMessageCases')]
    public function testParseMessage(string $message, Message $expected): void
    {
        $parser = self::createParser();
        $actual = $parser->parseMessage($message);
        self::assertEquals(
            $expected,
            $actual,
            sprintf(
                'Expected parsed result for message %s to match expected message.',
                var_export($actual, true)
            )
        );
    }

    public static function provideTestParseMessageCases(): iterable
    {
        yield 'Simple command' => [
            "PRIVMSG #test :Hey, I am a message!\r\n",
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: null,
                tags: null
            )
        ];

        yield 'Command + source' => [
            ":MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: new ImmutableSource(
                    nick: 'MrT',
                    user: 'timmy',
                    host: 'test.tld',
                ),
                tags: null
            )
        ];

        yield 'Command + tags' => [
            "@foo=bar;baz PRIVMSG #test :Hey, I am a message!\r\n",
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: null,
                tags: ImmutableTagList::createNormalizedTagList(
                    new ImmutableTag(
                        keyName: 'foo',
                        value: 'bar',
                        clientOnly: false,
                        vendor: null
                    ),
                    new ImmutableTag(
                        keyName: 'baz',
                        value: true,
                        clientOnly: false,
                        vendor: null
                    )
                )
            )
        ];

        yield 'Command + source + tags' => [
            "@foo=bar;baz :MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: new ImmutableSource(
                    nick: 'MrT',
                    user: 'timmy',
                    host: 'test.tld',
                ),
                tags: ImmutableTagList::createNormalizedTagList(
                    new ImmutableTag(
                        keyName: 'foo',
                        value: 'bar',
                        clientOnly: false,
                        vendor: null
                    ),
                    new ImmutableTag(
                        keyName: 'baz',
                        value: true,
                        clientOnly: false,
                        vendor: null
                    )
                )
            )
        ];

        yield 'Feature-rich message' => [
            "@test.php/foo=bar;+baz :MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: new ImmutableSource(
                    nick: 'MrT',
                    user: 'timmy',
                    host: 'test.tld',
                ),
                tags: ImmutableTagList::createNormalizedTagList(
                    new ImmutableTag(
                        keyName: 'foo',
                        value: 'bar',
                        clientOnly: false,
                        vendor: 'test.php'
                    ),
                    new ImmutableTag(
                        keyName: 'baz',
                        value: true,
                        clientOnly: true,
                        vendor: null
                    )
                )
            )
        ];
    }

    #[DataProvider('provideTestParseInvalidMessageCases')]
    public function testParseInvalidMessage(string $message): void
    {
        $parser = self::createParser();

        self::expectException(MalformedMessageException::class);
        $parser->parseMessage($message);
    }

    public static function provideTestParseInvalidMessageCases(): iterable
    {
        yield 'Empty string' => [''];
        yield 'Valid command without CRLF' => ['PRIVMSG #test :Hey, I am a message!'];
        yield 'Valid command without CR' => ["PRIVMSG #test :Hey, I am a message!\n"];
        yield 'Valid command without LF' => ["PRIVMSG #test :Hey, I am a message!\r"];
        yield 'Empty message' => ["\r\n"];
    }

    #[DataProvider('provideTestMessageTooLongCases')]
    public function testMessageTooLong(string $message): void
    {
        $parser = self::createParser();
        self::expectException(MessageTooLongException::class);
        $parser->parseMessage($message);
    }

    public static function provideTestMessageTooLongCases(): iterable
    {
        yield 'Core message too long' => [
            sprintf(
                // Static message part: 17 bytes
                "PRIVMSG #test :%s\r\n",
                str_repeat(
                    'a',
                    // This should be just 1 over the limit.
                    PCREMessageParser::MESSAGE_BYTE_LIMIT - 16
                )
            )
        ];

        yield 'Tags ok, core message too long' => [
            sprintf(
                // Tags + single space + core message
                // The total length of this string should fit within the
                // combined limits of the tags and original message lengths.
                '%s %s',
                // Tags
                sprintf(
                    // Static message part: 4 bytes
                    '@id=%s',
                    // This should be in-spec with 1 byte margin.
                    str_repeat(
                        'a',
                        PCREMessageParser::TAGS_ADDITIONAL_BYTE_LIMIT - 5
                    )
                ),
                // Core message, should be exactly correct, to trigger on the
                // whitespace separating the tags and this message.
                sprintf(
                    // Static message part: 17 bytes
                    "PRIVMSG #test :%s\r\n",
                    str_repeat(
                        'a',
                        // This should be exactly the limit, but it should fail
                        // because of the single space between tags and command.
                        PCREMessageParser::MESSAGE_BYTE_LIMIT - 17
                    )
                )
            )
        ];

        yield 'Tags too long, core message okay' => [
            sprintf(
                // Tags + single space + core message
                // The total length of this string should fit within the
                // combined limits of the tags and original message lengths.
                '%s %s',
                // Tags
                sprintf(
                    // Static message part: 4 bytes
                    '@id=%s',
                    // This should be out of spec by 1 byte.
                    str_repeat(
                        'a',
                        PCREMessageParser::TAGS_ADDITIONAL_BYTE_LIMIT - 3
                    )
                ),
                // Core message, should be 2 short, to allow for the tags to
                // exceed the length limit, without the total message exceeding
                // the total limit.
                sprintf(
                    // Static message part: 17 bytes
                    "PRIVMSG #test :%s\r\n",
                    str_repeat(
                        'a',
                        // This should produce a margin of 2.
                        // With the whitespace separating the sections, this leaves
                        // room for exactly 1 byte.
                        PCREMessageParser::MESSAGE_BYTE_LIMIT - 19
                    )
                )
            )
        ];
    }

    #[TestWith([":foo!foo@ PRIVMSG #test :Hey\r\n"], 'Empty host')]
    public function testInvalidSourceException(string $message): void
    {
        $parser = self::createParser();
        $exception = null;

        try {
            $parser->parseMessage($message);
        } catch (MalformedMessageException $parserException) {
            $exception = $parserException;
        }

        self::assertInstanceOf(MalformedMessageException::class, $exception);
        self::assertInstanceOf(MalformedSourceException::class, $exception->getPrevious());
    }

    #[TestWith(["1234\r\n"], 'Out of bounds numeric (4 digits instead of 3)')]
    public function testInvalidCommandException(string $message): void
    {
        $parser = self::createParser();
        $exception = null;

        try {
            $parser->parseMessage($message);
        } catch (MalformedMessageException $parserException) {
            $exception = $parserException;
        }

        self::assertInstanceOf(MalformedMessageException::class, $exception);
        self::assertInstanceOf(MalformedCommandException::class, $exception->getPrevious());
    }

    #[TestWith(["@id; PRIVMSG #test :Hey\r\n"], 'Trailing tag delimiter')]
    public function testInvalidTagsException(string $message): void
    {
        $parser = self::createParser();
        $exception = null;

        try {
            $parser->parseMessage($message);
        } catch (MalformedMessageException $parserException) {
            $exception = $parserException;
        }

        self::assertInstanceOf(MalformedMessageException::class, $exception);
        self::assertInstanceOf(MalformedTagListException::class, $exception->getPrevious());
    }
}
