<?php
declare(strict_types=1);

namespace Integration\Parser\Tag;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;
use JanMarten\IRC\Message\Exception\MalformedTagListException;
use JanMarten\IRC\Message\Exception\ParseException;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @todo Test against all possible escaped values
 * @todo Test against illegal unescape scenarios
 *
 * @see \JanMarten\IRC\Message\Component\ImmutableTagList::unescape
 */

#[CoversClass(PCRETagListParser::class)]
#[CoversClass(ImmutableTag::class)]
#[CoversClass(ImmutableTagList::class)]
#[CoversClass(MalformedTagListException::class)]
#[CoversClass(ParseException::class)]
final class PCRETagListParserTest extends TestCase
{
    public static function createParser(): TagListParser
    {
        return new PCRETagListParser();
    }

    #[DataProvider('provideTestParseTagsCases')]
    public function testParseTags(string $tags, callable ...$assertions): void
    {
        $parser = self::createParser();
        $parsedTags = $parser->parseTags($tags);

        self::assertNotCount(0, $assertions, 'Cannot test without assertions.');

        foreach ($assertions as $assertion) {
            $assertion($parsedTags);
        }
    }

    private static function assertTagListMatchesTags(array $expected, TagList $actual): void
    {
        self::assertEquals(
            array_reduce(
                $expected,
                fn (array $carry, Tag $tag) => [
                    ...$carry,
                    $tag->key => $tag
                ],
                []
            ),
            iterator_to_array($actual),
            'Expected tags to match.'
        );
    }

    private static function matchesTags(Tag ...$tags): callable
    {
        return fn (TagList $actual) => self::assertTagListMatchesTags($tags, $actual);
    }

    private static function isFlag(string $tag): callable
    {
        return static fn (TagList $actual) => self::assertTrue(
            $actual->is($tag),
            sprintf('Expected tag "%s" to be a flag.', $tag)
        );
    }

    private static function notFlag(string $tag): callable
    {
        return static fn (TagList $actual) => self::assertFalse(
            $actual->is($tag),
            sprintf('Expected tag "%s" to not be a flag.', $tag)
        );
    }

    private static function matchesUnescapedValue(string $tag, string $expected): callable
    {
        return static fn (TagList $actual) => self::assertEquals(
            $expected,
            $actual->unescape($tag),
            sprintf(
                'Expected unescaped value of tag "%s" to match %s',
                $tag,
                var_export($expected, true)
            )
        );
    }

    public static function provideTestParseTagsCases(): iterable
    {
        yield 'Single "standardized" tag w/ value' => [
            '@id=1',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: false,
                    vendor: null
                )
            ),
            self::notFlag('id'),
        ];

        yield 'Repeating tag' => [
            '@id=1;id=2',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '2',
                    clientOnly: false,
                    vendor: null
                )
            ),
            self::notFlag('id'),
        ];

        yield 'Client tag' => [
            '@+id=1',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: true,
                    vendor: null
                )
            ),
            self::notFlag('+id')
        ];

        yield 'Flag' => [
            '@id',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: false,
                    vendor: null
                )
            ),
            self::isFlag('id')
        ];

        yield 'Vendor tag' => [
            '@irc.vendor/id=1',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: false,
                    vendor: 'irc.vendor'
                )
            ),
            self::notFlag('irc.vendor/id')
        ];

        yield 'Vendor flag' => [
            '@irc.vendor/id',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: false,
                    vendor: 'irc.vendor'
                )
            ),
            self::isFlag('irc.vendor/id')
        ];

        yield 'Client only vendor tag' => [
            '@+irc.vendor/id=1',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: true,
                    vendor: 'irc.vendor'
                )
            ),
            self::notFlag('+irc.vendor/id')
        ];

        yield 'Multiple tags' => [
            '@+id=1;enabled',
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: true,
                    vendor: null
                ),
                new ImmutableTag(
                    keyName: 'enabled',
                    value: true,
                    clientOnly: false,
                    vendor: null
                )
            ),
            self::notFlag('+id'),
            self::isFlag('enabled')
        ];

        yield 'Client only, normal tag, matching' => [
            '@+id=1;id=2',
            // Client only tags take precedence
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: true,
                    vendor: null
                )
            ),
            self::notFlag('+id')
        ];

        yield 'Normal tag, client only, matching' => [
            '@id=1;+id=2',
            // Client only tags take precedence
            self::matchesTags(
                new ImmutableTag(
                    keyName: 'id',
                    value: '2',
                    clientOnly: true,
                    vendor: null
                )
            ),
            self::notFlag('+id')
        ];

        yield 'Escaped value, ;' => [
            '@id=foo\:bar;delayed',
            self::matchesUnescapedValue('id', 'foo;bar')
        ];

        // If a lone \ exists at the end of an escaped value (with no escape
        // character following it), then there SHOULD be no output character.
        yield 'Escaped value, trailing backslash' => [
            '@id=foo\;delayed',
            self::matchesUnescapedValue('id', 'foo')
        ];

        // If a multiple of \ exists at the end of an escaped value, then there
        // SHOULD be a corresponding output character.
        yield 'Escaped value, double trailing backslash' => [
            '@id=foo\\\\;delayed',
            self::matchesUnescapedValue('id', 'foo\\')
        ];
    }

    #[DataProvider('provideTestParseInvalidTagsCases')]
    public function testParseInvalidTags(string $tags): void
    {
        $parser = self::createParser();
        $exception = null;

        try {
            $parser->parseTags($tags);
        } catch (MalformedTagListException $parseException) {
            $exception = $parseException;
        }

        self::assertInstanceOf(
            MalformedTagListException::class,
            $exception,
            sprintf(
                'Expected that parsing %s would produce a malformed tag list exception.',
                var_export($tags, true)
            )
        );
    }

    public static function provideTestParseInvalidTagsCases(): iterable
    {
        yield 'Empty string' => [''];
        yield 'Empty tag list' => ['@'];
        yield 'Trailing tag delimiter' => ['@id=1;'];
        yield 'Invalid vendor' => ['@192.168.0.1/foo'];
    }
}
