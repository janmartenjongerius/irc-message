<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Builder;

use JanMarten\IRC\Message\Component\ImmutableTag;
use JanMarten\IRC\Message\Component\ImmutableTagList;
use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Contract\Component\TagList;

trait WithTags
{
    /**
     * @var array<Tag>
     */
    private array $tags = [];

    public TagList $tagList {
        get {
            return ImmutableTagList::createNormalizedTagList(
                ...array_values($this->tags)
            );
        }
    }

    public function withTags(TagList $tags): self
    {
        $this->tags = iterator_to_array($tags);
        return $this;
    }

    public function withAddedTag(
        string $keyName,
        string $value,
        ?string $vendor = null,
        bool $clientOnly = false
    ): static {
        $this->tags[] = new ImmutableTag(
            keyName: $keyName,
            value: $value,
            clientOnly: $clientOnly,
            vendor: $vendor
        );

        return $this;
    }

    public function withoutTags(): self
    {
        $this->tags = [];

        return $this;
    }

    public function withoutTag(string $keyName, ?string $vendor = null): self
    {
        $this->tags = array_filter(
            $this->tags,
            static fn (Tag $tag) => !(
                $tag->keyName === $keyName
                && $tag->vendor === $vendor
            )
        );

        return $this;
    }
}
