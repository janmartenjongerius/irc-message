<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Parser\Command;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Contract\Component\Command;
use JanMarten\IRC\Message\Contract\Parser\CommandParser;
use JanMarten\IRC\Message\Exception\MalformedCommandException;
use JanMarten\IRC\Message\Exception\ParseException;
use JanMarten\IRC\Message\Parser\Command\PCRECommandParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PCRECommandParser::class)]
#[CoversClass(ImmutableCommand::class)]
#[CoversClass(MalformedCommandException::class)]
#[CoversClass(ParseException::class)]
class PCRECommandParserTest extends TestCase
{
    public static function createParser(): CommandParser
    {
        return new PCRECommandParser();
    }

    #[DataProvider('provideTestParseCommandCases')]
    public function testParseCommand(
        string $command,
        Command $expectedCommand,
    ): void {
        $parser = self::createParser();
        $actualCommand = $parser->parseCommand($command);

        self::assertSame(
            $expectedCommand->verb,
            $actualCommand->verb,
            'Expected command verb to match.'
        );
        self::assertSame(
            $expectedCommand->arguments,
            $actualCommand->arguments,
            'Expected command arguments to match.'
        );
    }

    public static function provideTestParseCommandCases(): iterable
    {
        yield 'Simple PRIVMSG' => [
            'PRIVMSG #sales :Hello there!',
            new ImmutableCommand('PRIVMSG', '#sales', 'Hello there!')
        ];

        yield 'PING w/o token' => [
            'PING',
            new ImmutableCommand('PING')
        ];

        yield 'PING w/ token' => [
            'PING _',
            new ImmutableCommand('PING', '_')
        ];

        yield 'No trailing, multiple arguments' => [
            'CAP LS 302',
            new ImmutableCommand('CAP', 'LS', '302')
        ];

        yield 'Smiley' => [
            'PRIVMSG #emoji ::-)',
            new ImmutableCommand('PRIVMSG', '#emoji', ':-)')
        ];
    }

    #[DataProvider('provideTestMalformedCommandCases')]
    public function testMalformedCommand(string $malformedCommand): void
    {
        $parser = self::createParser();

        self::expectException(MalformedCommandException::class);
        $parser->parseCommand($malformedCommand);
    }

    public static function provideTestMalformedCommandCases(): iterable
    {
        yield 'Empty command' => [''];
        yield 'Mixed alpha-numeric command' => ['foo123'];
        yield 'Illegal :command character' => [':foo'];
        yield 'Unexpected CRLF' => ["FOO\r\n"];
    }
}
