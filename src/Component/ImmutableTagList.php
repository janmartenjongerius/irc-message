<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Component;

use JanMarten\IRC\Message\Contract\Component\Tag;
use JanMarten\IRC\Message\Contract\Component\TagList;
use LengthException;
use OutOfBoundsException;
use UnexpectedValueException;

final class ImmutableTagList implements TagList
{
    /**
     * @var array<string, Tag>
     */
    private array $tags;

    private function __construct(Tag ...$tags)
    {
        // Because client only tags should take precedence on normal tags, we
        // sort the list first.
        usort(
            $tags,
            static fn (Tag $a, Tag $b): int => (int)$a->clientOnly <=> (int)$b->clientOnly
        );

        $this->tags = array_reduce(
            $tags,
            static fn (array $carry, Tag $tag): array => [
                ...$carry,
                $tag->key => $tag
            ],
            []
        );
    }

    public static function createNormalizedTagList(Tag ...$tags): self
    {
        $normalized = [];
        $clientOnly = [];

        // Gather all tags.
        foreach ($tags as $tag) {
            // Keep client only tags separate for now.
            if ($tag->clientOnly) {
                $clientOnly[$tag->key] = $tag;
                continue;
            }

            // Record tags.
            $normalized[$tag->key] = $tag;
        }

        // Let client only tags override matching tags.
        foreach ($clientOnly as $tag) {
            $normalized[ltrim($tag->key, '+')] = $tag;
        }

        return new self(...$normalized);
    }

    /**
     * Get the value of the provided tag key.
     *
     * - If the tag is set and has a value, the value is returned.
     * - If the tag is set but has no value, true is returned.
     * - If the tag is not set, false is returned.
     *
     * The purpose of the true/false switching is to make tags without value
     *   behave like command flags.
     *
     * @param string $key E.g.: twitch.tv/ban-reason
     * @return string|bool
     */
    public function get(string $key): string|bool
    {
        return $this->tags[$key]?->value ?? false;
    }

    /**
     * Get the unescaped value for the provided tag key.
     *
     * @param string $key E.g.: twitch.tv/ban-reason
     * @return string
     *
     * @throws OutOfBoundsException when the tag for the given key does not exist.
     * @throws LengthException when the selected tag has no value.
     */
    public function unescape(string $key): string
    {
        if (!array_key_exists($key, $this->tags)) {
            throw new OutOfBoundsException(
                sprintf('Tag "%s" is not defined.', $key)
            );
        }

        $value = $this->tags[$key]->value;

        if (!is_string($value)) {
            throw new UnexpectedValueException(
                sprintf('Tag "%s" is not a string.', $key)
            );
        }

        if (strlen($value) === 0) {
            throw new LengthException(
                sprintf('Tag "%s" has no value.', $key)
            );
        }

        return self::unescapeValue($value);
    }

    public static function unescapeValue(string $value): string
    {
        $unescaped = strtr(
            $value,
            [
                '\:' => ';',
                '\s' => ' ',
                '\\\\' => '\\',
                '\r' => "\r",
                '\n' => "\n",
            ]
        );

        // If a lone \ exists at the end of an escaped value (with no escape
        // character following it), then there SHOULD be no output character.
        if (str_ends_with($value, '\\') && !str_ends_with($value, '\\' . '\\')) {
            $unescaped = substr($unescaped, 0, strlen($unescaped) - 1);
        }

        return $unescaped;
    }

    /**
     * This returns true if the result produced by the `get` method is exactly
     * true, otherwise this returns false.
     *
     * This is useful when switching on tags without values.
     *
     *     $message->tags->is('delayed');
     *
     * @param string $key E.g.: twitch.tv/ban-reason
     * @return bool
     * @see get
     */
    public function is(string $key): bool
    {
        return $this->get($key) === true;
    }

    public function current(): false|Tag
    {
        return current($this->tags);
    }

    public function key(): ?string
    {
        return key($this->tags);
    }

    public function next(): void
    {
        next($this->tags);
    }

    public function valid(): bool
    {
        return current($this->tags) !== false;
    }

    public function rewind(): void
    {
        reset($this->tags);
    }
}
