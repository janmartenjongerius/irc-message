<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Parser\Tag;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\TagList;
use JanMarten\IRC\Message\Contract\Parser\TagListParser;
use JanMarten\IRC\Message\Exception\EmptyTagException;
use JanMarten\IRC\Message\Exception\MalformedTagListException;
use JanMarten\IRC\Message\Exception\ParseException;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(PCRETagListParser::class)]
#[CoversClass(ImmutableTag::class)]
#[CoversClass(ImmutableTagList::class)]
#[CoversClass(MalformedTagListException::class)]
#[CoversClass(ParseException::class)]
#[CoversClass(EmptyTagException::class)]
final class PCRETagListParserTest extends TestCase
{
    public static function createParser(): TagListParser
    {
        return new PCRETagListParser();
    }

    #[DataProvider('provideTagListCases')]
    public function testParseTagsMatchesTagList(string $input, TagList $expected): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags($input);
        
        self::assertEquals(
            iterator_to_array($expected),
            iterator_to_array($actual),
            'Expected tags to match.'
        );
    }
    
    public static function provideTagListCases(): iterable
    {
        yield 'Single "standardized" tag w/ value' => [
            '@id=1',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: false,
                    vendor: null
                )
            ),
        ];

        yield 'Repeating tag' => [
            '@id=1;id=2',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '2',
                    clientOnly: false,
                    vendor: null
                )
            ),
        ];

        yield 'Client tag' => [
            '@+id=1',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: true,
                    vendor: null
                )
            ),
        ];

        yield 'Flag' => [
            '@id',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: false,
                    vendor: null
                )
            ),
        ];

        yield 'Vendor tag' => [
            '@irc.vendor/id=1',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: false,
                    vendor: 'irc.vendor'
                )
            ),
        ];

        yield 'Vendor flag' => [
            '@irc.vendor/id',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: false,
                    vendor: 'irc.vendor'
                )
            ),
        ];

        yield 'Client only vendor tag' => [
            '@+irc.vendor/id=1',
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: true,
                    clientOnly: true,
                    vendor: 'irc.vendor'
                )
            ),
        ];

        yield 'Multiple tags' => [
            '@+id=1;enabled',
            ImmutableTagList::createNormalizedTagList(
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
        ];

        yield 'Client only, normal tag, matching' => [
            '@+id=1;id=2',
            // Client only tags take precedence
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '1',
                    clientOnly: true,
                    vendor: null
                )
            ),
        ];

        yield 'Normal tag, client only, matching' => [
            '@id=1;+id=2',
            // Client only tags take precedence
            ImmutableTagList::createNormalizedTagList(
                new ImmutableTag(
                    keyName: 'id',
                    value: '2',
                    clientOnly: true,
                    vendor: null
                )
            ),
        ];
    }

    #[DataProvider('provideFlagCases')]
    public function testParseFlags(string $input, string $key): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags($input);

        self::assertTrue(
            $actual->is($key),
            sprintf('Expected tag "%s" to be a flag.', $key)
        );
        self::assertTrue(
            $actual->get($key),
            sprintf('Expected tag "%s" value to be true.', $key)
        );
    }

    public static function provideFlagCases(): iterable
    {
        yield 'Flag' => ['@id', 'id'];
        yield 'Vendor flag' => ['@irc.vendor/id', 'irc.vendor/id'];
        yield 'Multiple tags' => ['@+id=1;enabled', 'enabled'];
    }

    #[DataProvider('provideNonFlagCases')]
    public function testParseNonFlags(string $input, string $key, string $expectedValue): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags($input);

        self::assertFalse(
            $actual->is($key),
            sprintf('Expected tag "%s" to not be a flag.', $key)
        );

        $value = $actual->get($key);

        self::assertNotEmpty($value, 'Expected value to be non-empty for a non-flag tag.');
        self::assertEquals($expectedValue, $value, 'Expected values to match.');
    }
    
    public static function provideNonFlagCases(): iterable
    {
        yield 'Single "standardized" tag w/ value' => ['@id=1', 'id', '1'];
        yield 'Repeating tag' => ['@id=1;id=2', 'id', '2'];
        yield 'Client tag' => ['@+id=1', '+id', '1'];
        yield 'Vendor tag' => ['@irc.vendor/id=1', 'irc.vendor/id', '1'];
        yield 'Client only vendor tag' => ['@+irc.vendor/id=1', '+irc.vendor/id', '1'];
        yield 'Multiple tags' => ['@+id=1;enabled', '+id', '1'];

        // Client only tags take precedence
        yield 'Client only, normal tag, matching' => ['@+id=1;id=2', '+id', '1'];
        yield 'Normal tag, client only, matching' => ['@id=1;+id=2', '+id', '2'];
    }

    #[DataProvider('provideEscapedValueCases')]
    public function testParseEscapedValue(string $input, string $key, string $expectedUnescapedValue): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags($input);
        self::assertEquals(
            $expectedUnescapedValue,
            $actual->unescape($key),
            sprintf(
                'Expected unescaped value of tag "%s" to match %s',
                $key,
                print_r($expectedUnescapedValue, true)
            )
        );
    }

    public static function provideEscapedValueCases(): iterable
    {
        yield 'Escaped value, ;' => ['@id=foo\:bar;delayed', 'id', 'foo;bar'];

        // If a lone \ exists at the end of an escaped value (with no escape
        // character following it), then there SHOULD be no output character.
        yield 'Escaped value, trailing backslash' => ['@id=foo\;delayed', 'id', 'foo'];

        // If a multiple of \ exists at the end of an escaped value, then there
        // SHOULD be a corresponding output character.
        yield 'Escaped value, double trailing backslash' => [
            '@id=foo\\\\;delayed',
            'id',
            'foo\\'
        ];

        /**
         * This list contains all possible translations between escaped and
         *   unescaped sequences.
         */
        static $translations = [
            '\:' => ';',
            '\s' => ' ',
            '\\\\' => '\\',
            '\r' => "\r",
            '\n' => "\n",
        ];

        foreach ($translations as $escaped => $unescaped) {
            yield sprintf('Escaped sequence: "%s"', $escaped) => [
                sprintf('@id=[%s]', $escaped),
                'id',
                sprintf('[%s]', $unescaped)
            ];
        }
    }

    public function testUnescapeMissingTag(): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags('@foo');

        self::expectException(OutOfBoundsException::class);
        $actual->unescape('bar');
    }

    public function testUnescapeBooleanTag(): void
    {
        $parser = self::createParser();
        $actual = $parser->parseTags('@foo');

        self::expectException(UnexpectedValueException::class);
        $actual->unescape('foo');
    }

    public function testUnescapeEmptyTag(): void
    {
        $tagList = ImmutableTagList::createNormalizedTagList(
            new ImmutableTag(
                keyName: 'foo',
                value: '',
                clientOnly: false,
                vendor: null
            )
        );

        self::expectException(EmptyTagException::class);
        $tagList->unescape('foo');
    }

    #[DataProvider('provideInvalidTagsCases')]
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
                print_r($tags, true)
            )
        );
    }

    public static function provideInvalidTagsCases(): iterable
    {
        yield 'Empty string' => [''];
        yield 'Empty tag list' => ['@'];
        yield 'Trailing tag delimiter' => ['@id=1;'];
        yield 'Invalid vendor' => ['@192.168.0.1/foo'];
    }
}
