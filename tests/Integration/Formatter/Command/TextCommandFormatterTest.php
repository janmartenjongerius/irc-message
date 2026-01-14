<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Formatter\Command;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(TextCommandFormatter::class)]
final class TextCommandFormatterTest extends TestCase
{
    public function createFormatter(): TextCommandFormatter
    {
        return new TextCommandFormatter();
    }

    #[DataProvider('provideCommands')]
    public function testFormatCommand(Command $command, string $expected): void
    {
        $formatter = $this->createFormatter();
        $actual = $formatter->formatCommand($command);
        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($command, true)
            )
        );
    }

    public static function provideCommands(): iterable
    {
        yield 'Simple PRIVMSG' => [
            new ImmutableCommand('PRIVMSG', '#sales', 'Hello there!'),
            'PRIVMSG #sales :Hello there!',
        ];

        yield 'PING w/o token' => [
            new ImmutableCommand('PING'),
            'PING',
        ];

        yield 'PING w/ token' => [
            new ImmutableCommand('PING', '_'),
            'PING _',
        ];

        yield 'No trailing, multiple arguments' => [
            new ImmutableCommand('CAP', 'LS', '302'),
            'CAP LS 302',
        ];

        yield 'Smiley' => [
            new ImmutableCommand('PRIVMSG', '#emoji', ':-)'),
            'PRIVMSG #emoji ::-)',
        ];
    }
}
