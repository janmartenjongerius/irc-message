<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Helper;

use JanMarten\IRC\Message\Builder\MessageBuilder;
use JanMarten\IRC\Message\Component\ImmutableSource;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use function JanMarten\IRC\Message\message;

#[CoversFunction('JanMarten\IRC\Message\message')]
#[UsesClass(MessageBuilder::class)]
#[UsesClass(ImmutableSource::class)]
final class MessageHelperTest extends TestCase
{
    public function testMessageHelperDefaults(): void
    {
        self::assertInstanceOf(MessageBuilder::class, message());
    }

    public function testMessageHelperForSource(): void
    {
        self::assertInstanceOf(
            MessageBuilder::class,
            message(
                new ImmutableSource(
                    nick: 'test',
                    user: 'Test',
                    host: 'janmarten.name',
                )
            )
        );
    }
}
