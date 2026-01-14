<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Formatter\Tag;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Formatter\TagListFormatter;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;
use JanMarten\IRC\Message\Exception\EmptyTagException;
use JanMarten\IRC\Message\Exception\EmptyTagListException;
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextTagFormatter::class)]
#[UsesClass(PCRETagListParser::class)]
#[CoversClass(ImmutableTagList::class)]
#[CoversClass(ImmutableTag::class)]
#[CoversClass(EmptyTagException::class)]
final class TextTagFormatterTest extends TestCase
{
    private static function createParser(): TagListParser
    {
        return new PCRETagListParser();
    }

    private static function createFormatter(): TagListFormatter
    {
        return new TextTagFormatter();
    }

    #[DataProvider('provideTagLists')]
    public function testFormatTagList(TagList $tags, string $expected): void
    {
        $formatter = self::createFormatter();
        $actual = $formatter->formatTagList($tags);
        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($tags, true)
            )
        );
    }

    public static function provideTagLists(): iterable
    {
        $parser = self::createParser();

        yield 'Simple flag' => [$parser->parseTags('@id'), '@id'];
        yield 'Simple tag' => [$parser->parseTags('@foo=bar'), '@foo=bar'];
        yield 'Normalized tag' => [$parser->parseTags('@foo='), '@foo'];
        yield 'Client-only tag' => [$parser->parseTags('@+foo=bar'), '@+foo=bar'];
        yield 'Multiple tags' => [$parser->parseTags('@foo=bar;baz=bat'), '@foo=bar;baz=bat'];
        yield 'Vendor tag' => [
            $parser->parseTags('@irc.acme.org/explosive=tnt'),
            '@irc.acme.org/explosive=tnt'
        ];

        // Client-only tags have higher precedence.
        yield 'Generic + client-only tag' => [
            $parser->parseTags('@id=12;+id=13'),
            '@+id=13'
        ];
        yield 'Client-only + generic tag' => [
            $parser->parseTags('@+id=12;id=13'),
            '@+id=12'
        ];

        // Last tag wins.
        yield '3x id' => [
            $parser->parseTags('@id=1;id=2;id=3'),
            '@id=3'
        ];
        yield '2x id value, 1x id flag' => [
            $parser->parseTags('@id=1;id=2;id='),
            '@id'
        ];
    }

    public function testFormatEmptyTagList(): void
    {
        $formatter = self::createFormatter();

        self::expectException(EmptyTagListException::class);
        $formatter->formatTagList(ImmutableTagList::createNormalizedTagList());
    }

    public function testFormatEmptyTag(): void
    {
        $formatter = self::createFormatter();

        $tag = new ImmutableTag(
            keyName: 'foo',
            // This would normally have been normalized to `true`.
            value: '',
            clientOnly: false,
            vendor: null
        );

        $actualException = null;

        try {
            $formatter->formatTag($tag);
        } catch (EmptyTagException $exception) {
            $actualException = $exception;
            self::assertSame($tag, $actualException->tag);
        }

        self::assertInstanceOf(EmptyTagException::class, $actualException);
    }
}
