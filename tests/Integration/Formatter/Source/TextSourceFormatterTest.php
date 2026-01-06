<?php
declare(strict_types=1);

namespace Integration\Formatter\Source;

use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextSourceFormatter::class)]
final class TextSourceFormatterTest extends TestCase
{
    private static function createFormatter(): TextSourceFormatter
    {
        return new TextSourceFormatter();
    }

    #[DataProvider('provideSources')]
    public function testFormat(Source $source, string $expected): void
    {
        $formatter = self::createFormatter();

        $actual = $formatter->formatSource($source);
        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($source, true)
            )
        );
    }

    public static function provideSources(): iterable
    {
        yield 'Nick' => [
            new ImmutableSource(nick: 'coyote', user: null, host: null),
            ':coyote',
        ];

        yield 'Nick, Host' => [
            new ImmutableSource(nick: 'coyote', user: null, host: 'acme.com'),
            ':coyote@acme.com',
        ];

        yield 'Nick, Zero, Host' => [
            new ImmutableSource(nick: 'coyote', user: '0', host: 'acme.com'),
            ':coyote!0@acme.com',
        ];

        yield 'Nick, User, Host' => [
            new ImmutableSource(nick: 'coyote', user: 'w.e.', host: 'acme.com'),
            ':coyote!w.e.@acme.com',
        ];
    }
}
