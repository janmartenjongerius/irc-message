<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Helper;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Message;
use JanMarten\IRC\Message\Contract\Component\MessageComponent;
use JanMarten\IRC\Message\Formatter\Command\TextCommandFormatter;
use JanMarten\IRC\Message\Formatter\Message\TextMessageFormatter;
use JanMarten\IRC\Message\Formatter\Source\TextSourceFormatter;
use JanMarten\IRC\Message\Formatter\Tag\TextTagFormatter;
use JanMarten\IRC\Message\Tests\Integration\Formatter\Command\TextCommandFormatterTest;
use JanMarten\IRC\Message\Tests\Integration\Formatter\Message\TextMessageFormatterTest;
use JanMarten\IRC\Message\Tests\Integration\Formatter\Source\TextSourceFormatterTest;
use JanMarten\IRC\Message\Tests\Integration\Formatter\Tag\TextTagFormatterTest;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use function JanMarten\IRC\Message\format;

#[CoversFunction('JanMarten\IRC\Message\format')]
#[UsesClass(TextCommandFormatter::class)]
#[UsesClass(TextMessageFormatter::class)]
#[UsesClass(TextTagFormatter::class)]
#[UsesClass(TextSourceFormatter::class)]
#[UsesClass(ImmutableTagList::class)]
#[UsesClass(ImmutableTag::class)]
final class FormatHelperTest extends TestCase
{
    #[DataProviderExternal(TextCommandFormatterTest::class, 'provideCommands')]
    #[DataProviderExternal(TextMessageFormatterTest::class, 'provideMessages')]
    #[DataProviderExternal(TextSourceFormatterTest::class, 'provideSources')]
    #[DataProviderExternal(TextTagFormatterTest::class, 'provideTagLists')]
    public function testFormat(
        Message|MessageComponent $ircMessageComponent,
        string $expected
    ): void {
        $actual = format($ircMessageComponent);

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($ircMessageComponent, true)
            )
        );
    }

    public function testFormatTag(): void
    {
        $tag = new ImmutableTag(
            keyName: 'foo',
            value: 'bar',
            clientOnly: true,
            vendor: 'acme'
        );

        $expected = '+acme/foo=bar';
        $actual = format($tag);

        self::assertSame(
            $expected,
            $actual,
            sprintf(
                'Expected "%s" to be the text format of %s',
                $expected,
                print_r($tag, true)
            )
        );
    }

    public function testFormatUnsupportedComponent(): void
    {
        self::expectException(\InvalidArgumentException::class);
        format(new class implements MessageComponent {});
    }
}
