<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Parser\Source;

use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Exception\MalformedSourceException;
use JanMarten\IRC\Message\Exception\ParseException;
use JanMarten\IRC\Message\Parser\Source\PCRESourceParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PCRESourceParser::class)]
#[CoversClass(ImmutableSource::class)]
#[CoversClass(MalformedSourceException::class)]
#[CoversClass(ParseException::class)]
class PCRESourceParserTest extends TestCase
{
    public static function createParser(): PCRESourceParser
    {
        return new PCRESourceParser();
    }

    #[DataProvider('provideParseSourceCases')]
    public function testParseSource(string $source, Source $expected): void
    {
        $parser = self::createParser();
        $actual = $parser->parseSource($source);

        self::assertEquals($expected->nick, $actual->nick, 'Expected Nick to match');
        self::assertEquals($expected->user, $actual->user, 'Expected User to match');
        self::assertEquals($expected->host, $actual->host, 'Expected Host to match');

        self::assertEquals($expected->mask, $actual->mask, 'Expected source masks to match');
        self::assertEquals($source, $actual->mask, 'Expected source masks to raw source');
    }

    public static function provideParseSourceCases(): iterable
    {
        yield 'Nick' => [
            ':coyote',
            new ImmutableSource(nick: 'coyote', user: null, host: null),
        ];

        yield 'Nick, Host' => [
            ':coyote@acme.com',
            new ImmutableSource(nick: 'coyote', user: null, host: 'acme.com'),
        ];

        yield 'Nick, Zero, Host' => [
            ':coyote!0@acme.com',
            new ImmutableSource(nick: 'coyote', user: '0', host: 'acme.com'),
        ];

        yield 'Nick, User, Host' => [
            ':coyote!w.e.@acme.com',
            new ImmutableSource(nick: 'coyote', user: 'w.e.', host: 'acme.com'),
        ];
    }

    #[DataProvider('provideParseInvalidSourceCases')]
    public function testParseInvalidSource(string $source): void
    {
        $parser = self::createParser();
        $actualException = null;

        try {
            $parser->parseSource($source);
        } catch (MalformedSourceException $exception) {
            $actualException = $exception;
        }

        self::assertInstanceOf(MalformedSourceException::class, $actualException);
    }

    public static function provideParseInvalidSourceCases(): iterable
    {
        yield 'Invalid user' => [':coyote!@acme.com'];
        yield 'Missing delimiter' => ['coyote!foo@acme.com'];
        yield 'Empty host' => [':coyote!foo@'];
    }
}
