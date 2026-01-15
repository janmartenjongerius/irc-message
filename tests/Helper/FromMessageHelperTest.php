<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Helper;

use JanMarten\IRC\Message\Builder\CommandMessageBuilder;
use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use function JanMarten\IRC\Message\fromMessage;

#[CoversFunction('JanMarten\IRC\Message\fromMessage')]
#[UsesClass(CommandMessageBuilder::class)]
#[UsesClass(ImmutableMessage::class)]
#[UsesClass(ImmutableCommand::class)]
#[UsesClass(ImmutableTagList::class)]
final class FromMessageHelperTest extends TestCase
{
    public function testFromMessageHelper(): void
    {
        self::assertInstanceOf(
            CommandMessageBuilder::class,
            fromMessage(
                new ImmutableMessage(
                    command: new ImmutableCommand('PING'),
                    source: null,
                    tags: ImmutableTagList::createNormalizedTagList()
                )
            )
        );
    }
}
