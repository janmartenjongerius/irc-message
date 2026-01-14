<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Tests\Component;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Exception\EmptyTagException;
use OutOfBoundsException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

#[CoversClass(ImmutableTagList::class)]
#[UsesClass(ImmutableTag::class)]
#[CoversClass(EmptyTagException::class)]
class ImmutableTagListTest extends TestCase
{
    public function testContains(): void
    {
        $tagList = ImmutableTagList::createNormalizedTagList(
            new ImmutableTag(
                keyName: 'id',
                value: true,
                clientOnly: false,
                vendor: 'janmarten.name'
            )
        );

        self::assertFalse(
            $tagList->contains('id'),
            'Expected to not find a tag for "id"'
        );
        self::assertTrue(
            $tagList->contains('janmarten.name/id'),
            'Expected to find a tag for "janmarten.name/id"'
        );
    }

    public function testUnescapeNonExistentTag(): void
    {
        $tagList = ImmutableTagList::createNormalizedTagList();
        self::expectException(OutOfBoundsException::class);
        $tagList->unescape('id');
    }

    public function testUnescapeBooleanTag(): void
    {
        $tagList = ImmutableTagList::createNormalizedTagList(
            new ImmutableTag(
                keyName: 'id',
                value: true,
                clientOnly: false,
                vendor: null
            )
        );

        self::expectException(UnexpectedValueException::class);
        $tagList->unescape('id');
    }

    public function testUnescapeEmptyTag(): void
    {
        $tagList = ImmutableTagList::createNormalizedTagList(
            new ImmutableTag(
                keyName: 'id',
                value: '',
                clientOnly: false,
                vendor: null
            )
        );

        self::expectException(EmptyTagException::class);
        $tagList->unescape('id');
    }
}
