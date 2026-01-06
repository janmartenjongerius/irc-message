<?php
declare(strict_types=1);

namespace Integration\Formatter\Message;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use JanMarten\IRC\Message\Formatter\Message\TextMessageFormatter;
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextMessageFormatter::class)]
#[UsesClass(TextCommandFormatter::class)]
#[UsesClass(TextSourceFormatter::class)]
#[UsesClass(TextTagFormatter::class)]
#[UsesClass(ImmutableTagList::class)]
final class TextMessageFormatterTest extends TestCase
{
    public static function createFormatter(): TextMessageFormatter
    {
        return new TextMessageFormatter(
            commandFormatter: new TextCommandFormatter(),
            sourceFormatter: new TextSourceFormatter(),
            tagListFormatter: new TextTagFormatter()
        );
    }

    #[DataProvider('provideMessages')]
    public function testFormatMessage(Message $message, string $expected): void
    {
        $formatter = self::createFormatter();
        $actual = $formatter->formatMessage($message);

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($message, true)
            )
        );
    }

    public static function provideMessages(): iterable
    {
        yield 'Simple command' => [
            new ImmutableMessage(
                command: new ImmutableCommand(
                    'PRIVMSG',
                    '#test',
                    'Hey, I am a message!',
                ),
                source: null,
                tags: ImmutableTagList::createNormalizedTagList()
            ),
            "PRIVMSG #test :Hey, I am a message!\r\n",
        ];

        yield 'Command + source' => [
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
                tags: ImmutableTagList::createNormalizedTagList()
            ),
            ":MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
        ];

        yield 'Command + tags' => [
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
            ),
            "@foo=bar;baz PRIVMSG #test :Hey, I am a message!\r\n",
        ];

        yield 'Command + source + tags' => [
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
            ),
            "@foo=bar;baz :MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
        ];

        yield 'Feature-rich message' => [
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
            ),
            "@test.php/foo=bar;+baz :MrT!timmy@test.tld PRIVMSG #test :Hey, I am a message!\r\n",
        ];
    }
}
