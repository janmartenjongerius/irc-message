<?php
declare(strict_types=1);

namespace JanMarten\IRC\Message\Contract\Component;

use LengthException;
use OutOfBoundsException;

interface TagList extends \Iterator
{
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
    public function get(string $key): string|bool;

    /**
     * Get the unescaped value for the provided tag key.
     *
     * @param string $key E.g.: twitch.tv/ban-reason
     * @return string
     *
     * @throws OutOfBoundsException when the tag for the given key does not exist.
     * @throws LengthException when the selected tag has no value.
     */
    public function unescape(string $key): string;

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
    public function is(string $key): bool;

    public function current(): false|Tag;

    public function key(): ?string;
}
