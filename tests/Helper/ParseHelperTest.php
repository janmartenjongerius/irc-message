<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Helper;

use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Parser\Command\PCRECommandParser;
use JanMarten\IRC\Message\Parser\Message\PCREMessageParser;
use JanMarten\IRC\Message\Parser\Source\PCRESourceParser;
use JanMarten\IRC\Message\Parser\Tag\PCRETagListParser;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use function JanMarten\IRC\Message\parse;

#[CoversFunction('JanMarten\IRC\Message\parse')]
#[UsesClass(ImmutableCommand::class)]
#[UsesClass(ImmutableMessage::class)]
#[UsesClass(ImmutableSource::class)]
#[UsesClass(ImmutableTag::class)]
#[UsesClass(ImmutableTagList::class)]
#[UsesClass(PCRECommandParser::class)]
#[UsesClass(PCREMessageParser::class)]
#[UsesClass(PCRESourceParser::class)]
#[UsesClass(PCRETagListParser::class)]
final class ParseHelperTest extends TestCase
{
    #[TestWith(['@id=1 :test!Test@janmarten.name PRIVMSG #test Hello'])]
    public function testParseHelper(string $message): void
    {
        self::assertInstanceOf(Message::class, parse($message));
    }
}
