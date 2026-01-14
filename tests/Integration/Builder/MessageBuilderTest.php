<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Integration\Builder;

use JanMarten\IRC\Message\Builder\CommandMessageBuilder;
use JanMarten\IRC\Message\Builder\MessageBuilder;
use JanMarten\IRC\Message\Builder\WithArguments;
use JanMarten\IRC\Message\Builder\WithSource;
use JanMarten\IRC\Message\Builder\WithTags;
use JanMarten\IRC\Message\Component\ImmutableCommand;
use JanMarten\IRC\Message\Component\ImmutableMessage;
use JanMarten\IRC\Message\Component\ImmutableSource;
use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Source;
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use JanMarten\IRC\Message\Formatter\Message\TextMessageFormatter;
use JanMarten\IRC\Message\Formatter\Source\CreatesSourceMask;
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesFunction;
use PHPUnit\Framework\Attributes\UsesTrait;
use PHPUnit\Framework\TestCase;
use function JanMarten\IRC\Message\command;
use function JanMarten\IRC\Message\format;
use function JanMarten\IRC\Message\message;

#[CoversClass(MessageBuilder::class)]
#[CoversClass(CommandMessageBuilder::class)]
#[CoversTrait(WithArguments::class)]
#[CoversTrait(WithSource::class)]
#[CoversTrait(WithTags::class)]
#[UsesTrait(CreatesSourceMask::class)]
#[UsesClass(ImmutableCommand::class)]
#[UsesClass(ImmutableMessage::class)]
#[UsesClass(ImmutableSource::class)]
#[UsesClass(ImmutableTagList::class)]
#[UsesClass(ImmutableTag::class)]
#[UsesClass(TextCommandFormatter::class)]
#[UsesClass(TextMessageFormatter::class)]
#[UsesClass(TextSourceFormatter::class)]
#[UsesClass(TextTagFormatter::class)]
#[UsesFunction('JanMarten\IRC\Message\format')]
#[UsesFunction('JanMarten\IRC\Message\message')]
#[UsesFunction('JanMarten\IRC\Message\command')]
final class MessageBuilderTest extends TestCase
{
    #[TestWith(['PING'])]
    #[TestWith(['PING', '_'])]
    #[TestWith(['PRIVMSG', '#test', 'Hello, World!'])]
    public function testBuildCommandMessage(string $verb, string ...$arguments): void
    {
        $builder = message()
            ->withAddedTag('method', __METHOD__)
            ->command($verb, ...$arguments);
        $message = $builder->build();

        self::assertSame(format($message), "$builder");

        $command = command($message)
            ->withAddedTag('class', __CLASS__)
            ->build();

        self::assertSame(__METHOD__, $command->tags->unescape('method'));
        self::assertSame(__CLASS__, $command->tags->unescape('class'));
    }

    public function testWithArguments(): void
    {
        $message = message()
            ->command(__FUNCTION__)
            ->withArguments('#test', 'Hello,')
            ->withAddedArgument('World!')
            ->build();

        self::assertSame(
            ['#test', 'Hello,', 'World!'],
            $message->command->arguments
        );

        $message = command($message)
            ->withArguments('foo')
            ->build();

        self::assertSame(['foo'], $message->command->arguments);
    }

    public function testWithSource(): void
    {
        $message = message()->command(__FUNCTION__)->build();
        self::assertInstanceOf(Source::class, $message->source);

        $message = command($message)->withoutSource()->build();
        self::assertNull($message->source);

        $source = new ImmutableSource(nick: __FUNCTION__, user: __METHOD__, host: __CLASS__);
        $message = command($message)->withSource($source)->build();
        self::assertSame($source, $message->source);

        $message = command($message)->withSourceFromEnv()->build();
        self::assertSame(format(ImmutableSource::fromEnv()), format($message->source));
    }

    public function testWithTags(): void
    {
        $builder = message()
            ->withAddedTag('method', __METHOD__)
            ->command(__FUNCTION__);

        $message = $builder->build();

        self::assertSame(__METHOD__, $message->tags->unescape('method'));

        $command = command($message)
            ->withoutTag('method')
            ->build();

        self::assertFalse($command->tags->contains('method'));

        $command = command($message)
            ->withoutTags()
            ->build();

        self::assertFalse($command->tags->contains('method'));

        $command = command($message)
            ->withoutTags()
            ->withTags($message->tags)
            ->build();

        self::assertSame(__METHOD__, $command->tags->unescape('method'));
    }
}
